#!/usr/bin/env node
/**
 * Teste de interface de ponta a ponta (portal do profissional e do paciente)
 * em um Chrome headless real, sem dependências npm.
 *
 * Requisitos: Node 22+ (WebSocket nativo) e Google Chrome/Chromium.
 * Rode contra um servidor local com SQLite TEMPORÁRIO populado pelo seed:
 * o teste cria pacientes, agendamentos e procedimentos.
 *
 *   NUVA_UI_BASE_URL=http://127.0.0.1:8000 NUVA_UI_ALLOW_MUTATIONS=1 \
 *     node scripts/ui-e2e-test.mjs [--shots=/caminho/para/capturas]
 *
 * CHROME_PATH aponta outro executável do Chrome, se necessário.
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const base = (process.env.NUVA_UI_BASE_URL || '').replace(/\/+$/, '');
const shotDir = process.argv.find((a) => a.startsWith('--shots='))?.slice(8) || '';
if (!base || process.env.NUVA_UI_ALLOW_MUTATIONS !== '1') {
  console.error('Defina NUVA_UI_BASE_URL e NUVA_UI_ALLOW_MUTATIONS=1 (o teste grava dados).');
  process.exit(2);
}
if (!['127.0.0.1', 'localhost', '[::1]'].includes(new URL(base).hostname)) {
  console.error('Por segurança, o teste de interface só roda contra localhost.');
  process.exit(2);
}
if (shotDir) mkdirSync(shotDir, { recursive: true });

// ------------------------------------------------------------------ Chrome
const chromePath = process.env.CHROME_PATH || [
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/Applications/Chromium.app/Contents/MacOS/Chromium',
  '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser',
].find((p) => existsSync(p));
if (!chromePath) { console.error('Chrome não encontrado. Defina CHROME_PATH.'); process.exit(2); }

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const port = 9300 + Math.floor(Math.random() * 600);
const profileDir = mkdtempSync(join(tmpdir(), 'nuva-ui-'));
const chrome = spawn(chromePath, ['--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profileDir}`, '--no-first-run', '--no-default-browser-check', '--disable-gpu', 'about:blank'], { stdio: 'ignore' });
const shutdown = () => {
  try { chrome.kill(); } catch { /* already closed */ }
  // Chrome may still be flushing its profile; leftovers in the OS temp dir are harmless.
  try { rmSync(profileDir, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 }); } catch { /* ignore */ }
};
process.on('exit', shutdown);

let pageTarget;
for (let i = 0; i < 50 && !pageTarget; i++) {
  await sleep(200);
  try { pageTarget = (await (await fetch(`http://127.0.0.1:${port}/json/list`)).json()).find((t) => t.type === 'page'); } catch { /* starting */ }
}
if (!pageTarget) { console.error('Não foi possível conectar ao Chrome.'); process.exit(1); }

const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let seq = 0;
const pending = new Map();
const consoleErrors = [];
const httpErrors = [];
const dialogs = [];
const send = (method, params = {}) => new Promise((resolve) => {
  const id = ++seq;
  pending.set(id, resolve);
  ws.send(JSON.stringify({ id, method, params }));
});
ws.addEventListener('message', (event) => {
  const msg = JSON.parse(event.data);
  if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); return; }
  const p = msg.params;
  if (msg.method === 'Runtime.consoleAPICalled' && ['error', 'warning', 'assert'].includes(p.type)) consoleErrors.push(`console.${p.type}: ` + p.args.map((a) => a.value ?? a.description).join(' '));
  if (msg.method === 'Runtime.exceptionThrown') consoleErrors.push('exceção: ' + (p.exceptionDetails.exception?.description || p.exceptionDetails.text));
  // HTTP errors are expected in the negative tests; they are listed, not failed.
  if (msg.method === 'Log.entryAdded' && p.entry.level === 'error' && !p.entry.text.startsWith('Failed to load resource')) consoleErrors.push(`log: ${p.entry.text}`);
  if (msg.method === 'Network.responseReceived' && p.response.status >= 400) httpErrors.push(`${p.response.status} ${new URL(p.response.url).pathname}`);
  if (msg.method === 'Page.javascriptDialogOpening') { dialogs.push(p.message); send('Page.handleJavaScriptDialog', { accept: true }); }
});

// ------------------------------------------------------------------ helpers
const evaluate = async (expression) => {
  const res = await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
  if (res.result?.exceptionDetails) throw new Error(res.result.exceptionDetails.exception?.description?.split('\n')[0] || 'falha ao avaliar');
  return res.result?.result?.value;
};
const js = JSON.stringify;
const waitFor = async (expression, timeout = 6000) => {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    try { if (await evaluate(expression)) return true; } catch { /* navigating */ }
    await sleep(100);
  }
  return false;
};
const clearToast = () => evaluate(`document.querySelector('#nuva-toast')?.remove(); true`);
const waitToast = async () => { await waitFor(`!!document.querySelector('#nuva-toast')`); return evaluate(`document.querySelector('#nuva-toast')?.textContent || ''`); };
const click = (sel) => evaluate(`(() => { const el = document.querySelector(${js(sel)}); if (!el) throw new Error('não encontrado: ' + ${js(sel)}); el.click(); return true; })()`);
const clickRow = (rowSel, text, btnSel) => evaluate(`(() => { const row = [...document.querySelectorAll(${js(rowSel)})].find((r) => r.innerText.includes(${js(text)})); if (!row) throw new Error('linha não encontrada: ' + ${js(text)}); row.querySelector(${js(btnSel)}).click(); return true; })()`);
const fill = (sel, value) => evaluate(`(() => { const el = document.querySelector(${js(sel)}); if (!el) throw new Error('não encontrado: ' + ${js(sel)}); el.value = ${js(value)}; el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); return true; })()`);
const selectOption = (sel, text) => evaluate(`(() => { const s = document.querySelector(${js(sel)}); const o = [...s.options].find((x) => x.textContent.includes(${js(text)})); if (!o) throw new Error('opção não encontrada: ' + ${js(text)}); s.value = o.value; return true; })()`);
const submit = (sel) => evaluate(`(() => { const f = document.querySelector(${js(sel)}); if (!f) throw new Error('formulário não encontrado: ' + ${js(sel)}); f.requestSubmit(); return true; })()`);
const text = (sel) => evaluate(`document.querySelector(${js(sel)})?.innerText || ''`);
const count = (sel) => evaluate(`document.querySelectorAll(${js(sel)}).length`);
const goto = async (path) => {
  await send('Page.navigate', { url: base + path });
  await sleep(300);
  await waitFor(`document.readyState === 'complete' && !document.body.innerText.includes('Carregando o sistema')`);
  await sleep(600);
};
const shot = async (name) => {
  if (!shotDir) return;
  const res = await send('Page.captureScreenshot', { format: 'png' });
  writeFileSync(join(shotDir, `${name}.png`), Buffer.from(res.result.data, 'base64'));
};
const api = (method, path, body) => evaluate(`fetch(${js(path)}, { method: ${js(method)}, headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + localStorage.getItem('auth_token') }, body: ${body ? js(js(body)) : 'undefined'} }).then(async (r) => ({ status: r.status, body: r.status === 204 ? null : await r.json() }))`);
const setViewport = (width, height, mobile) => send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: mobile ? 2 : 1, mobile });
const logout = async () => { await evaluate(`localStorage.clear(); true`); await goto('/login'); };
const login = async (email, password, home) => {
  await fill('#login-email', email); await fill('#login-password', password); await submit('form[data-form="login"]');
  if (!(await waitFor(`location.pathname === ${js(home)} && !!document.querySelector('.section-title')`))) throw new Error(`login de ${email} não abriu ${home}`);
  await sleep(500);
};
const expect = (cond, message) => { if (!cond) throw new Error(message); };
// Page-level horizontal scroll, plus controls clipped by an overflow:hidden container.
const assertFitsViewport = async () => {
  const overflow = await evaluate(`document.documentElement.scrollWidth - window.innerWidth`);
  expect(overflow <= 1, `${overflow}px de rolagem horizontal`);
  const clipped = await evaluate(`[...document.querySelectorAll('button, a, input, select, textarea')]
    .filter((el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && (r.right > window.innerWidth + 1 || r.left < -1); })
    .map((el) => (el.innerText || el.getAttribute('aria-label') || el.name || el.tagName).trim().slice(0, 30))`);
  expect(clipped.length === 0, `fora da tela: ${clipped.join(', ')}`);
};

const results = [];
const step = async (name, fn) => {
  const before = consoleErrors.length;
  try {
    const detail = await fn();
    const errors = consoleErrors.slice(before);
    results.push({ ok: errors.length === 0, name, detail: errors.length ? `${detail ?? ''} | ${errors.join(' ; ')}` : (detail ?? '') });
  } catch (error) {
    results.push({ ok: false, name, detail: error.message });
  }
};

await send('Runtime.enable'); await send('Log.enable'); await send('Page.enable'); await send('Network.enable');
await send('Page.addScriptToEvaluateOnNewDocument', { source: "document.addEventListener('securitypolicyviolation', (e) => console.error('CSP', e.violatedDirective, e.blockedURI))" });
await setViewport(1440, 900, false);

// Unique data per run, so the test can be repeated on the same database.
const run = Date.now().toString(36).slice(-5);
const pad = (n) => String(n).padStart(2, '0');
const isoDay = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const futureDay = isoDay(new Date(Date.now() + (40 + Math.floor(Math.random() * 300)) * 86400000));
const today = isoDay(new Date());
const PRO = { email: 'dr.gabriel@nuva.com.br', password: 'senha123', name: 'Dr. Gabriel Silva' };

// ================================================================== PROFISSIONAL
await step('Rota protegida sem sessão vai para o login', async () => {
  await goto('/pro');
  expect(await waitFor(`location.pathname === '/login'`), `ficou em ${await evaluate('location.pathname')}`);
});
await step('Login com senha errada mostra erro', async () => {
  await fill('#login-email', PRO.email); await fill('#login-password', 'errada123');
  await clearToast(); await submit('form[data-form="login"]');
  const t = await waitToast(); expect(/inválid/i.test(t), `aviso: "${t}"`); return t;
});
await step('Login do profissional abre o painel (sem aviso antigo)', async () => {
  await login(PRO.email, PRO.password, '/');
  expect(!(await evaluate(`!!document.querySelector('#nuva-toast.error')`)), 'aviso de erro continuou na tela');
  await shot('pro-01-painel');
  return await text('.section-title');
});
await step('Painel: métricas e próximos atendimentos em aberto', async () => {
  const metrics = await evaluate(`[...document.querySelectorAll('.metric-value')].map((e) => e.innerText.trim())`);
  expect(metrics.length === 3, `métricas: ${metrics}`);
  const badges = await evaluate(`[...document.querySelectorAll('.data-list .status-badge')].map((b) => b.innerText.trim())`);
  expect(badges.every((b) => ['Agendado', 'Confirmado'].includes(b)), `próximos com status ${badges}`);
  return `métricas ${js(metrics)}`;
});

await step('Agenda: semana, dia, lista, navegação e filtros', async () => {
  await click('[data-nav="/pro"]'); await waitFor(`!!document.querySelector('[data-action="set-agenda-view"]')`); await sleep(400);
  await shot('pro-02-agenda-semana');
  await click('[data-action="set-agenda-view"][data-view="day"]'); await sleep(300);
  const d0 = await text('.agenda-main'); await click('[data-action="agenda-next"]'); await sleep(300);
  expect(d0 !== (await text('.agenda-main')), 'navegação não mudou o dia');
  await click('[data-action="agenda-today"]'); await click('[data-action="set-agenda-view"][data-view="list"]'); await sleep(400);
  const all = await text('.agenda-main');
  expect(all.includes('Alegra') && all.includes('Jheniffer'), 'agendamentos de demonstração ausentes');
  await click('[data-action="filter-agenda-status"][data-status="confirmed"]'); await sleep(300);
  const badges = await evaluate(`[...document.querySelectorAll('.agenda-main .status-badge')].map((b) => b.innerText.trim())`);
  await click('[data-action="filter-agenda-status"][data-status="all"]'); await sleep(300);
  expect(badges.length > 0 && badges.every((b) => b === 'Confirmado'), `filtro confirmado: ${badges}`);
  await click('[data-action="mc-next-month"]'); await click('[data-action="mc-prev-month"]'); await sleep(200);
});

let appointmentId = 0;
const openModalFor = async (patient, procedure, at, notes) => {
  await click('[data-action="open-appointment-modal"]'); await waitFor(`!!document.querySelector('.agenda-modal')`);
  await selectOption('#modal-patient', patient);
  if (procedure) await selectOption('#modal-procedure', procedure);
  await fill('#modal-date', at); if (notes) await fill('#modal-notes', notes);
};
await step('Agenda: criar agendamento pelo modal', async () => {
  await openModalFor('Jefferson', 'Fios', `${futureDay}T09:00`, `UI ${run}`);
  await clearToast(); await submit('form[data-form="appointment"]');
  const t = await waitToast(); expect(t === 'Agendamento criado.', `aviso: "${t}"`);
  expect(await waitFor(`!document.querySelector('.agenda-modal')`), 'modal não fechou');
  appointmentId = (await api('GET', '/api/appointments')).body.data.find((a) => a.notes === `UI ${run}`)?.id || 0;
  expect(appointmentId > 0, 'agendamento não encontrado'); return t;
});
await step('Agenda: conflito de horário avisa e mantém o modal aberto', async () => {
  await openModalFor('Jheniffer', null, `${futureDay}T10:00`);
  await clearToast(); await submit('form[data-form="appointment"]');
  const t = await waitToast(); expect(/conflita|indisponível/i.test(t), `aviso: "${t}"`);
  expect(await evaluate(`!!document.querySelector('.agenda-modal')`), 'modal fechou após o erro');
  await shot('pro-03-conflito');
  await click('.agenda-modal [data-action="close-appointment-modal"]');
  expect(await waitFor(`!document.querySelector('.agenda-modal')`), 'X não fechou o modal'); return t;
});
await step('Agenda: editar agendamento', async () => {
  await click(`[data-action="edit-appointment"][data-id="${appointmentId}"]`); await waitFor(`!!document.querySelector('.agenda-modal')`);
  expect((await evaluate(`document.querySelector('#modal-notes').value`)) === `UI ${run}`, 'notas não carregaram');
  await fill('#modal-notes', `UI ${run} editado`); await clearToast(); await submit('form[data-form="appointment"]');
  const t = await waitToast(); expect(t === 'Agendamento atualizado.', `aviso: "${t}"`); return t;
});
const statusActions = () => evaluate(`[...document.querySelectorAll('[data-action="appointment-status"][data-id="${appointmentId}"]')].map((b) => b.innerText.trim())`);
await step('Agenda: ações acompanham o status (agendado → confirmado → concluído)', async () => {
  expect(js(await statusActions()) === js(['Confirmar', 'Concluir', 'Cancelar']), `agendado: ${await statusActions()}`);
  const sameRow = await evaluate(`new Set([...document.querySelector('[data-action="delete-appointment"][data-id="${appointmentId}"]').parentElement.querySelectorAll('button')].map((b) => Math.round(b.getBoundingClientRect().top))).size === 1`);
  expect(sameRow, 'botões empilhados');
  await clearToast(); await click(`[data-action="appointment-status"][data-status="confirmed"][data-id="${appointmentId}"]`);
  expect((await waitToast()) === 'Agendamento confirmado.', 'confirmar falhou'); await sleep(300);
  expect(js(await statusActions()) === js(['Concluir', 'Cancelar']), `confirmado: ${await statusActions()}`);
  await clearToast(); await click(`[data-action="appointment-status"][data-status="completed"][data-id="${appointmentId}"]`);
  expect((await waitToast()) === 'Agendamento concluído.', 'concluir falhou'); await sleep(300);
  expect((await statusActions()).length === 0, 'concluído ainda tem ações de status');
  await shot('pro-04-agenda-lista');
});
await step('Agenda: cancelar (com confirmação) e excluir', async () => {
  await openModalFor('Jefferson', null, `${futureDay}T15:00`, `UI ${run} cancelar`);
  await clearToast(); await submit('form[data-form="appointment"]'); await waitToast(); await sleep(400);
  const id = (await api('GET', '/api/appointments')).body.data.find((a) => a.notes === `UI ${run} cancelar`).id;
  await clearToast(); await click(`[data-action="appointment-status"][data-status="canceled"][data-id="${id}"]`);
  const t1 = await waitToast(); expect(t1 === 'Agendamento cancelado.', `aviso: "${t1}"`);
  await clearToast(); await click(`[data-action="delete-appointment"][data-id="${id}"]`);
  const t2 = await waitToast(); await sleep(300);
  expect(!(await evaluate(`!!document.querySelector('[data-action="delete-appointment"][data-id="${id}"]')`)), 'item continua na lista');
  return `${t1} → ${t2}`;
});
await step('Painel: atendimento de hoje concluído entra nas métricas e sai dos próximos', async () => {
  const created = await api('POST', '/api/appointments', { patient_id: (await api('GET', '/api/patients')).body.data.find((p) => p.name.includes('Jefferson')).id, scheduled_at: `${today} 00:0${Math.floor(Math.random() * 10)}`, notes: `UI ${run} hoje` });
  expect(created.status === 201, `criação: HTTP ${created.status}`);
  await api('PATCH', `/api/appointments/${created.body.data.id}/status`, { status: 'completed' });
  await goto('/');
  const [todayCount, doneCount] = await evaluate(`[...document.querySelectorAll('.metric-value')].slice(0, 2).map((e) => Number(e.innerText))`);
  const upcoming = await text('.data-list');
  await api('DELETE', `/api/appointments/${created.body.data.id}`);
  expect(todayCount >= 1 && doneCount >= 1, `hoje=${todayCount} concluídos=${doneCount}`);
  expect(!upcoming.includes(`UI ${run} hoje`), 'concluído apareceu nos próximos');
  return `hoje=${todayCount} concluídos hoje=${doneCount}`;
});

const newPatient = { name: `Paciente UI ${run}`, email: `ui-${run}@example.test` };
await step('Pacientes: lista, cadastro, e-mail repetido e busca', async () => {
  await goto('/pro/pacientes');
  const body = await text('.content-body');
  expect(['Alegra', 'Jheniffer', 'Jefferson'].every((n) => body.includes(n)), 'pacientes de demonstração ausentes');
  await fill('#patient-name', newPatient.name); await fill('#patient-cpf', '123.456.789-09'); await fill('#patient-phone', '(11) 90000-0000');
  await fill('#patient-email', newPatient.email); await fill('#patient-birthdate', '1990-05-20'); await fill('#patient-allergies', 'Dipirona');
  await clearToast(); await submit('form[data-form="patient"]'); const t1 = await waitToast(); await sleep(400);
  expect((await text('.patients-list')).includes(newPatient.name), `não entrou na lista (${t1})`);
  await fill('#patient-name', 'Duplicado'); await fill('#patient-cpf', '1'); await fill('#patient-phone', '1'); await fill('#patient-email', newPatient.email);
  await clearToast(); await submit('form[data-form="patient"]'); const t2 = await waitToast();
  expect(/e-mail/i.test(t2), `duplicado: "${t2}"`);
  await fill('.patients-search input[name="query"]', `UI ${run}`); await submit('form[data-form="patient-search"]'); await sleep(600);
  expect((await count('.patients-search li')) === 1, 'busca não achou o paciente');
  await shot('pro-05-pacientes');
});
await step('Prontuário: registro inicial, novo registro, data e link de ativação', async () => {
  await clickRow('.patients-list li', newPatient.name, 'a');
  await waitFor(`!!document.querySelector('form[data-form="medical-record"]')`); await sleep(400);
  const body = await text('.content-body');
  expect(body.includes('Dipirona') && body.includes('20/05/1990'), 'registro inicial ou data de nascimento incorretos');
  await fill('#record-notes', `Evolução UI ${run}`); await clearToast(); await submit('form[data-form="medical-record"]'); await waitToast(); await sleep(400);
  expect((await text('.content-body')).includes(`Evolução UI ${run}`), 'registro não apareceu');
  await clearToast(); await submit('form[data-form="activation-link"]'); await waitToast(); await sleep(400);
  const link = await evaluate(`document.querySelector('#activation-link-url')?.value || ''`);
  expect(link.startsWith(`${base}/ativar-conta?`), `link: "${link}"`);
  await shot('pro-06-prontuario');
});
await step('Prontuário inexistente mostra erro amigável', async () => {
  await goto('/pro/pacientes/prontuario/999999999');
  expect(/não foi possível/i.test(await text('#app')), 'sem mensagem de erro');
});
await step('Procedimentos: criar, editar e excluir', async () => {
  await goto('/pro/procedimentos');
  const name = `Peeling UI ${run}`;
  await fill('#procedure-name', name); await fill('#procedure-price', '350'); await fill('#procedure-duration', '40');
  await clearToast(); await submit('form[data-form="procedure"]'); await waitToast(); await sleep(400);
  await clickRow('.data-list li', name, '[data-action="edit-procedure"]'); await sleep(300);
  await fill('#procedure-name', `${name} editado`); await clearToast(); await submit('form[data-form="procedure"]'); await waitToast(); await sleep(400);
  expect((await text('.content-body')).includes(`${name} editado`), 'não editou');
  await clearToast(); await clickRow('.data-list li', `${name} editado`, '[data-action="delete-procedure"]'); const t = await waitToast(); await sleep(400);
  expect(!(await text('.content-body')).includes(`${name} editado`), 'não excluiu'); return t;
});
await step('Configurações: perfil, senha errada e senha nova', async () => {
  await goto('/configuracoes');
  await fill('#profile-name', `${PRO.name} UI`); await clearToast(); await submit('form[data-form="profile"]'); await waitToast(); await sleep(400);
  expect((await text('.user-name')).includes('UI'), 'cabeçalho não atualizou');
  await fill('#current-password', 'errada123'); await fill('#new-password', 'NovaSenha123'); await fill('#new-password-confirmation', 'NovaSenha123');
  await clearToast(); await submit('form[data-form="password"]'); expect(/incorreta/i.test(await waitToast()), 'senha errada aceita');
  await fill('#current-password', PRO.password); await clearToast(); await submit('form[data-form="password"]');
  expect(/atualizada/i.test(await waitToast()), 'troca de senha falhou');
});
await step('Tema escuro alterna de verdade', async () => {
  await click('[data-action="toggle-theme"]'); await sleep(300);
  const dark = await evaluate(`getComputedStyle(document.body).backgroundColor.match(/\\d+/g).slice(0, 3).map(Number).reduce((a, b) => a + b) < 200`);
  await shot('pro-07-tema-escuro'); await click('[data-action="toggle-theme"]');
  expect(dark, 'fundo continua claro');
});
await step('Profissional não entra no portal do paciente', async () => {
  await goto('/paciente'); expect(await waitFor(`location.pathname === '/'`), `ficou em ${await evaluate('location.pathname')}`);
});
await step('Logout, login com a senha nova e restauração da conta de demonstração', async () => {
  await click('[data-action="logout"]'); expect(await waitFor(`location.pathname === '/login'`), 'logout falhou');
  await login(PRO.email, 'NovaSenha123', '/');
  const r1 = await api('PUT', '/api/user/password', { current_password: 'NovaSenha123', password: PRO.password, password_confirmation: PRO.password });
  const r2 = await api('PUT', '/api/user/profile', { name: PRO.name, email: PRO.email });
  expect(r1.status === 200 && r2.status === 200, `restauração: ${r1.status}/${r2.status}`);
});
await setViewport(390, 844, true);
await step('Celular: agenda abre no dia, com os atendimentos antes do calendário', async () => {
  await goto('/pro'); await shot('pro-08-celular-agenda');
  const mainTop = await evaluate(`document.querySelector('.agenda-main').getBoundingClientRect().top`);
  const sideTop = await evaluate(`document.querySelector('.agenda-sidebar').getBoundingClientRect().top`);
  const dayActive = await evaluate(`document.querySelector('[data-view="day"]').classList.contains('active')`);
  const mainHeight = await evaluate(`document.querySelector('.agenda-main').getBoundingClientRect().height`);
  expect(mainTop < sideTop, 'calendário antes dos atendimentos');
  expect(dayActive, 'não abriu na visão dia');
  expect(mainHeight > 200, `área da agenda espremida (${Math.round(mainHeight)}px)`);
  await assertFitsViewport();
});
await step('Celular: pacientes mostra busca e lista antes do cadastro', async () => {
  await goto('/pro/pacientes'); await shot('pro-09-celular-pacientes');
  const tops = await evaluate(`['.patients-search', '.patients-list', '.patients-new'].map((s) => document.querySelector(s).getBoundingClientRect().top)`);
  expect(tops[0] < tops[1] && tops[1] < tops[2], `ordem: ${tops.map(Math.round)}`);
});
for (const path of ['/', '/pro', '/pro/pacientes', '/pro/procedimentos', '/configuracoes']) {
  await step(`Celular: ${path} cabe na tela`, async () => {
    await goto(path);
    await assertFitsViewport();
  });
}
await setViewport(1440, 900, false);
await logout();

// ================================================================== PACIENTE
const patientEmail = `ui-paciente-${run}@example.test`;
await step('Paciente: cadastro público', async () => {
  await goto('/cadastro');
  expect(!(await evaluate(`!!document.querySelector('input[name="role"][value="professional"]')`)), 'opção de profissional no cadastro público');
  await fill('#register-name', `Paciente Portal ${run}`); await fill('#register-email', patientEmail);
  await fill('#register-password', 'Senha-Paciente-1'); await fill('#register-confirmation', 'Senha-Paciente-1');
  await submit('form[data-form="register"]');
  expect(await waitFor(`location.pathname === '/paciente' && !!document.querySelector('.section-title')`), 'não abriu o portal do paciente');
  await sleep(400);
  expect((await text('#app')).includes('Você ainda não possui agendamentos'), 'painel vazio sem mensagem');
  await shot('pac-01-painel-vazio');
});
await step('Paciente: busca profissional e agenda', async () => {
  await click('[data-nav][href="/paciente/busca"]'); await waitFor(`!!document.querySelector('form[data-form="professional-search"]')`);
  await fill('form[data-form="professional-search"] input[name="query"]', 'Gabriel'); await submit('form[data-form="professional-search"]'); await sleep(700);
  await clickRow('.data-list li', 'Gabriel', '[data-action="select-professional"]'); await sleep(300);
  await fill('#booking-at', `${futureDay}T17:00`); await fill('#booking-notes', `Pedido UI ${run}`);
  await clearToast(); await submit('form[data-form="patient-booking"]'); const t = await waitToast();
  expect(await waitFor(`location.pathname === '/paciente'`), `aviso: "${t}"`); await sleep(500);
  const upcoming = await text('.patient-upcoming');
  expect(upcoming.includes('Gabriel') && upcoming.includes('Agendado'), 'agendamento não aparece nos próximos');
  await shot('pac-02-agendado'); return t;
});
await step('Paciente: não agenda no passado nem em horário ocupado', async () => {
  await goto('/paciente/busca');
  await fill('form[data-form="professional-search"] input[name="query"]', 'Gabriel'); await submit('form[data-form="professional-search"]'); await sleep(700);
  await clickRow('.data-list li', 'Gabriel', '[data-action="select-professional"]'); await sleep(300);
  await fill('#booking-at', '2020-01-10T10:00'); await clearToast(); await submit('form[data-form="patient-booking"]');
  const past = await waitToast(); expect(/passou|futuro/i.test(past), `passado: "${past}"`);
  await fill('#booking-at', `${futureDay}T17:15`); await clearToast(); await submit('form[data-form="patient-booking"]');
  const busy = await waitToast(); expect(/conflita|indisponível/i.test(busy), `ocupado: "${busy}"`);
  return `${past} / ${busy}`;
});
await step('Paciente: cancelar move o atendimento para o histórico', async () => {
  await goto('/paciente');
  await clearToast(); await clickRow('.patient-upcoming li', 'Gabriel', '[data-action="cancel-patient-appointment"]'); await waitToast(); await sleep(500);
  expect(!(await text('.patient-upcoming')).includes('Gabriel'), 'cancelado continua nos próximos');
  const history = await text('.patient-history');
  expect(history.includes('Cancelado') && !(await evaluate(`!!document.querySelector('.patient-history [data-action="cancel-patient-appointment"]')`)), 'histórico incorreto');
  await shot('pac-03-historico');
});
await step('Paciente: prontuário vazio e perfil', async () => {
  await goto('/paciente/prontuario');
  expect(/nenhum registro/i.test(await text('#app')), 'prontuário vazio sem mensagem');
  await goto('/paciente/perfil');
  await fill('#patient-profile-phone', '(11) 95555-0000'); await fill('#patient-profile-birth', '1993-03-15');
  await clearToast(); await submit('form[data-form="profile"]'); const t = await waitToast();
  await goto('/paciente/perfil');
  const saved = await evaluate(`[document.querySelector('#patient-profile-phone').value, document.querySelector('#patient-profile-birth').value]`);
  expect(js(saved) === js(['(11) 95555-0000', '1993-03-15']), `perfil salvo: ${saved} (${t})`);
  await fill('#patient-current-password', 'errada123'); await fill('#patient-new-password', 'Senha-Paciente-2'); await fill('#patient-password-confirmation', 'Senha-Paciente-2');
  await clearToast(); await submit('form[data-form="password"]'); expect(/incorreta/i.test(await waitToast()), 'senha errada aceita');
  await shot('pac-04-perfil');
});
await step('Paciente não entra no portal do profissional', async () => {
  await goto('/pro'); expect(await waitFor(`location.pathname === '/paciente'`), `ficou em ${await evaluate('location.pathname')}`);
});
await step('Paciente com histórico (Alegra): atendimento confirmado e prontuário', async () => {
  await logout(); await login('alegra@example.com', 'senha123', '/paciente');
  expect((await text('.patient-upcoming')).includes('Confirmado'), 'atendimento confirmado ausente');
  await goto('/paciente/prontuario');
  expect((await text('#app')).includes('linhas de expressão'), 'registro clínico ausente');
});
await setViewport(390, 844, true);
for (const path of ['/paciente', '/paciente/busca', '/paciente/prontuario', '/paciente/perfil']) {
  await step(`Celular: ${path} cabe na tela`, async () => {
    await goto(path); if (path === '/paciente') await shot('pac-05-celular-painel');
    await assertFitsViewport();
  });
}
await setViewport(1440, 900, false);
await step('Telas públicas: recuperação de senha e link incompleto', async () => {
  await logout(); await goto('/recuperar-senha');
  expect(/clínica/i.test(await text('#app')), 'texto de recuperação ausente');
  await goto('/ativar-conta');
  expect(/incompleto/i.test(await text('#app')), 'link incompleto sem aviso');
});

// ------------------------------------------------------------------ report
ws.close();
let failed = 0;
for (const r of results) {
  if (!r.ok) failed++;
  console.log(`${r.ok ? 'PASS' : 'FAIL'} ${r.name}${r.detail ? ` — ${r.detail}` : ''}`);
}
console.log(`\nRespostas HTTP de erro (esperadas nos testes negativos): ${httpErrors.join(', ') || 'nenhuma'}`);
console.log(`Diálogos confirmados: ${dialogs.length}`);
console.log(`\nResult: ${results.length - failed}/${results.length} passed${failed ? `, ${failed} failed.` : '.'}`);
process.exit(failed ? 1 : 0);
