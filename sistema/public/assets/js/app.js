const app = document.querySelector('#app');

const state = {
  renderId: 0,
  user: readStoredUser(),
  token: localStorage.getItem('auth_token'),
  cache: {
    appointment: null,
    procedure: null,
    patientSearch: [],
    professionalSearch: [],
    selectedProfessional: null,
  },
};

function readStoredUser() {
  try {
    const value = localStorage.getItem('user');
    return value ? JSON.parse(value) : null;
  } catch {
    localStorage.removeItem('user');
    return null;
  }
}

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function initials(name = '') {
  return String(name)
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase() || 'NU';
}

function currentHome() {
  if (state.user?.role === 'patient') return '/paciente';
  if (state.user?.role === 'professional') return '/';
  return '/login';
}

function saveSession(payload) {
  state.token = payload.token;
  state.user = payload.user;
  localStorage.setItem('auth_token', payload.token);
  localStorage.setItem('user', JSON.stringify(payload.user));
}

function clearSession() {
  state.token = null;
  state.user = null;
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
}

async function api(path, options = {}) {
  const { method = 'GET', body, headers = {} } = options;
  const requestHeaders = { Accept: 'application/json', ...headers };

  if (body !== undefined) {
    requestHeaders['Content-Type'] = 'application/json';
  }

  if (state.token) {
    requestHeaders.Authorization = `Bearer ${state.token}`;
  }

  const response = await fetch(path, {
    method,
    headers: requestHeaders,
    credentials: 'same-origin',
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json')
    ? await response.json().catch(() => ({}))
    : {};

  if (!response.ok) {
    if (response.status === 401 && state.token) {
      clearSession();
      if (!isPublicPath(location.pathname)) {
        navigate('/login', true);
      }
    }
    const error = new Error(payload.message || payload.error || `Erro HTTP ${response.status}`);
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload;
}

function isPublicPath(path) {
  return ['/login', '/cadastro', '/recuperar-senha', '/ativar-conta'].includes(path);
}

function navigate(path, replace = false) {
  const target = new URL(path, location.origin);
  if (target.pathname === location.pathname && target.search === location.search) {
    return;
  }
  history[replace ? 'replaceState' : 'pushState']({}, '', `${target.pathname}${target.search}`);
  render();
}

function showToast(message, kind = 'success') {
  document.querySelector('#nuva-toast')?.remove();
  const toast = document.createElement('div');
  toast.id = 'nuva-toast';
  toast.className = `nuva-toast ${kind}`;
  toast.textContent = message;
  document.body.append(toast);
  window.setTimeout(() => toast.remove(), 4200);
}

async function loadCurrentUser() {
  if (!state.token) return null;
  const user = await api('/api/user');
  state.user = user;
  localStorage.setItem('user', JSON.stringify(user));
  return user;
}

function routeForPath(path) {
  const normalizedPath = path.length > 1 ? path.replace(/\/+$/, '') : path;
  const recordMatch = normalizedPath.match(/^\/pro\/pacientes\/prontuario\/(\d+)$/);
  if (recordMatch) return { name: 'patient-record', role: 'professional', patientId: Number(recordMatch[1]) };

  const routes = {
    '/': { name: 'dashboard', role: 'professional' },
    '/login': { name: 'login' },
    '/cadastro': { name: 'register' },
    '/recuperar-senha': { name: 'recover' },
    '/ativar-conta': { name: 'activate' },
    '/paciente': { name: 'patient-dashboard', role: 'patient' },
    '/paciente/dashboard': { name: 'patient-dashboard', role: 'patient' },
    '/paciente/busca': { name: 'patient-search', role: 'patient' },
    '/paciente/prontuario': { name: 'patient-records', role: 'patient' },
    '/paciente/perfil': { name: 'patient-profile', role: 'patient' },
    '/pro': { name: 'agenda', role: 'professional' },
    '/pro/procedimentos': { name: 'procedures', role: 'professional' },
    '/pro/pacientes': { name: 'patients', role: 'professional' },
    '/configuracoes': { name: 'settings', role: 'professional' },
  };

  return routes[normalizedPath] || { name: 'not-found' };
}

function authLayout(content) {
  return `<main class="auth-body"><div class="auth-container"><section class="auth-card card">${content}</section></div></main>`;
}

function loginView() {
  return authLayout(`
    <header class="auth-header" style="text-align:center; margin-bottom: 2rem;">
      <p class="brand-logo" style="justify-content:center; margin-bottom:.6rem;">Nüva<span class="brand-dot">.</span></p>
      <h1>Bem-vindo de volta</h1>
      <p class="auth-subtitle">Acesse sua conta para continuar.</p>
    </header>
    <form class="auth-form" data-form="login">
      <div class="form-group"><label for="login-email">E-mail</label><input id="login-email" name="email" type="email" autocomplete="email" required placeholder="seu@email.com"></div>
      <div class="form-group"><label for="login-password">Senha</label><div class="password-field"><input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="Sua senha"><button type="button" class="password-toggle" data-action="toggle-password" data-target="login-password" aria-label="Mostrar senha"><i class="ph ph-eye"></i></button></div></div>
      <p class="form-help"><a href="/recuperar-senha" data-nav>Esqueceu a senha?</a></p>
      <button class="load-btn" type="submit">Entrar</button>
    </form>
    <p class="switch-link">Ainda não tem uma conta? <a href="/cadastro" data-nav>Crie uma agora.</a></p>
  `);
}

function registerView() {
  return authLayout(`
    <header class="auth-header" style="text-align:center; margin-bottom: 1.5rem;">
      <p class="brand-logo" style="justify-content:center; margin-bottom:.6rem;">Nüva<span class="brand-dot">.</span></p>
      <h1>Crie sua conta</h1>
      <p class="auth-subtitle">Escolha seu perfil e comece a usar o portal.</p>
    </header>
    <form class="auth-form" data-form="register">
      <fieldset class="role-selector"><legend>Como você usará o Nüva?</legend><label class="role-card active"><input type="radio" name="role" value="patient" checked><i class="ph-fill ph-user"></i><span><strong>Sou paciente</strong><small>Busco profissionais e acompanho meus tratamentos.</small></span></label><label class="role-card"><input type="radio" name="role" value="professional"><i class="ph-fill ph-stethoscope"></i><span><strong>Sou profissional</strong><small>Gerencio agenda, pacientes e procedimentos.</small></span></label></fieldset>
      <div class="form-group"><label for="register-name">Nome</label><input id="register-name" name="name" required autocomplete="name" placeholder="Seu nome completo"></div>
      <div class="form-group"><label for="register-email">E-mail</label><input id="register-email" name="email" type="email" required autocomplete="email" placeholder="seu@email.com"></div>
      <div class="form-group"><label for="register-password">Senha</label><div class="password-field"><input id="register-password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo de 8 caracteres"><button type="button" class="password-toggle" data-action="toggle-password" data-target="register-password" aria-label="Mostrar senha"><i class="ph ph-eye"></i></button></div></div>
      <div class="form-group"><label for="register-confirmation">Confirme a senha</label><input id="register-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" placeholder="Repita a senha"></div>
      <button class="load-btn" type="submit">Criar conta</button>
    </form>
    <p class="switch-link">Já possui uma conta? <a href="/login" data-nav>Fazer login</a></p>
  `);
}

function simpleView(title, body, icon = 'ph-info') {
  return authLayout(`<header class="auth-header" style="text-align:center"><i class="ph ${icon}" style="font-size:3rem;color:var(--md-sys-color-primary)"></i><h1>${escapeHtml(title)}</h1><p class="auth-subtitle">${escapeHtml(body)}</p><p class="switch-link"><a href="/login" data-nav>Voltar para o login</a></p></header>`);
}

function formatDateTime(value) {
  if (!value) return 'Sem data';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date);
}

function asDateTimeInput(value) {
  return value ? escapeHtml(String(value).replace(' ', 'T').slice(0, 16)) : '';
}

function apiList(payload) {
  return Array.isArray(payload) ? payload : (payload?.data || []);
}

function emptyState(message) {
  return `<div class="empty-state">${escapeHtml(message)}</div>`;
}

function statusBadge(status = 'scheduled') {
  return `<span class="status-badge ${escapeHtml(status)}">${escapeHtml(status)}</span>`;
}

function navButton(path, active, icon, label) {
  return `<button class="menu-btn ${active === path ? 'active' : ''}" data-nav="${path}" title="${escapeHtml(label)}"><i class="${icon}"></i><span class="menu-label">${escapeHtml(label)}</span></button>`;
}

function professionalLayout(active, title, content) {
  const name = escapeHtml(state.user?.name || 'Profissional');
  return `<main class="app"><aside class="sidebar"><button class="menu-btn" data-action="toggle-sidebar" id="mobileToggle" title="Menu lateral"><i class="ph ph-list"></i><span class="menu-label">Menu</span></button>${navButton('/', active, 'ph-fill ph-house', 'Início')}${navButton('/pro', active, 'ph ph-calendar-blank', 'Agenda')}${navButton('/pro/pacientes', active, 'ph ph-users', 'Pacientes')}${navButton('/pro/procedimentos', active, 'ph ph-magic-wand', 'Procedimentos')}${navButton('/configuracoes', active, 'ph ph-gear', 'Ajustes')}<div class="menu-bottom"><button class="menu-btn" data-action="toggle-theme" title="Alternar tema"><i class="ph ph-moon"></i><span class="menu-label">Tema</span></button></div></aside><section class="content"><header class="content-top"><nav class="breadcrumb" aria-label="Navegação"><span>Clínica</span> / <strong>${escapeHtml(title)}</strong></nav><div class="top-actions"><span class="user-name">${name}</span><button class="profile" data-action="logout" title="Sair da conta">${initials(state.user?.name)}</button></div></header><div class="content-body">${content}</div></section></main>`;
}

function patientLayout(title, content) {
  const name = escapeHtml(state.user?.name || 'Paciente');
  return `<main><header class="patient-nav"><a href="/paciente" data-nav class="brand-logo">Nüva<span class="brand-dot">.</span></a><nav class="patient-nav-links" aria-label="Portal do paciente"><a href="/paciente" data-nav>Início</a><a href="/paciente/busca" data-nav>Buscar profissional</a><a href="/paciente/prontuario" data-nav>Prontuário</a><a href="/paciente/perfil" data-nav>Meu perfil</a></nav><div class="nav-profile"><span>${name}</span><button class="avatar-small" data-action="logout" title="Sair">${initials(state.user?.name)}</button></div></header><section class="patient-container"><div class="discover-section"><p class="breadcrumb"><span>Portal do paciente</span> / <strong>${escapeHtml(title)}</strong></p>${content}</div></section></main>`;
}

function appointmentRows(appointments, options = {}) {
  if (!appointments.length) return emptyState(options.emptyMessage || 'Nenhum agendamento encontrado.');
  return `<ul class="data-list">${appointments.map((appointment) => {
    const personName = options.patient
      ? appointment.professional?.name
      : appointment.patient?.name;
    const patientName = personName || 'Consulta';
    const procedure = appointment.procedure?.name || 'Consulta clínica';
    const actionMarkup = options.patient
      ? (appointment.status !== 'canceled' && appointment.status !== 'completed'
        ? `<button class="action-btn outline danger-button" data-action="cancel-patient-appointment" data-id="${Number(appointment.id)}">Cancelar</button>`
        : '')
      : `<div class="toolbar"><button class="action-btn outline" data-action="edit-appointment" data-id="${Number(appointment.id)}">Editar</button><button class="action-btn outline" data-action="appointment-status" data-id="${Number(appointment.id)}" data-status="confirmed">Confirmar</button><button class="action-btn outline danger-button" data-action="delete-appointment" data-id="${Number(appointment.id)}">Excluir</button></div>`;
    return `<li><div class="data-row-main"><strong>${escapeHtml(patientName)}</strong><span>${escapeHtml(procedure)} · ${formatDateTime(appointment.scheduled_at)}</span></div><div class="toolbar">${statusBadge(appointment.status)}${actionMarkup}</div></li>`;
  }).join('')}</ul>`;
}

async function professionalDashboardView() {
  const [dashboard, appointmentPayload] = await Promise.all([
    api('/api/professional/dashboard'),
    api('/api/appointments'),
  ]);
  const appointments = apiList(appointmentPayload);
  const today = new Date().toISOString().slice(0, 10);
  const todayAppointments = appointments.filter((item) => String(item.scheduled_at).slice(0, 10) === today);
  const completeCount = todayAppointments.filter((item) => item.status === 'completed').length;
  const name = dashboard?.user?.name || state.user?.name || 'Profissional';

  return professionalLayout('/', 'Início', `<section class="page-header"><h1 class="section-title">Olá, ${escapeHtml(name)}</h1><p class="page-intro">Acompanhe sua operação clínica em um painel leve e nativo.</p></section><div class="three-columns"><article class="page-panel"><span class="muted">Agendamentos hoje</span><p class="metric-value">${todayAppointments.length}</p></article><article class="page-panel"><span class="muted">Atendimentos concluídos</span><p class="metric-value">${completeCount}</p></article><article class="page-panel"><span class="muted">Especialidade</span><p class="metric-value" style="font-size:1.25rem">${escapeHtml(dashboard?.specialty || 'Não informada')}</p></article></div><section class="page-panel"><div class="toolbar" style="justify-content:space-between"><div><h2>Próximos atendimentos</h2><p class="muted">Dados carregados da API clínica.</p></div><a class="action-btn outline" href="/pro" data-nav>Ver agenda</a></div>${appointmentRows(appointments.slice(0, 5), { emptyMessage: 'Nenhum agendamento futuro.' })}</section>`);
}

async function agendaView() {
  const [appointmentPayload, proceduresPayload, patientsPayload] = await Promise.all([
    api('/api/appointments'),
    api('/api/procedures'),
    api('/api/patients'),
  ]);
  const appointments = apiList(appointmentPayload);
  const procedures = apiList(proceduresPayload);
  const patients = apiList(patientsPayload);
  state.cache.appointments = appointments;
  const editing = state.cache.appointment;

  const patientOptions = patients.map((patient) => `<option value="${Number(patient.id)}" ${Number(editing?.patient_id) === Number(patient.id) ? 'selected' : ''}>${escapeHtml(patient.name || 'Paciente sem nome')}</option>`).join('');
  const procedureOptions = procedures.map((procedure) => `<option value="${Number(procedure.id)}" ${Number(editing?.procedure_id) === Number(procedure.id) ? 'selected' : ''}>${escapeHtml(procedure.name)}</option>`).join('');
  const formTitle = editing ? 'Editar agendamento' : 'Novo agendamento';

  return professionalLayout('/pro', 'Agenda', `<section class="page-header"><h1 class="section-title">Agenda</h1><p class="page-intro">Crie, atualize, confirme ou exclua os atendimentos da sua clínica.</p></section><div class="two-columns"><section class="page-panel"><h2>${formTitle}</h2><form data-form="appointment" data-id="${editing ? Number(editing.id) : ''}"><div class="form-group"><label for="appointment-patient">Paciente</label><select id="appointment-patient" name="patient_id" required ${editing ? 'disabled' : ''}><option value="">Selecione um paciente</option>${patientOptions}</select></div><div class="form-group"><label for="appointment-procedure">Procedimento</label><select id="appointment-procedure" name="procedure_id"><option value="">Consulta clínica</option>${procedureOptions}</select></div><div class="form-group"><label for="appointment-date">Data e horário</label><input id="appointment-date" name="scheduled_at" type="datetime-local" required value="${asDateTimeInput(editing?.scheduled_at)}"></div><div class="form-group"><label for="appointment-notes">Observações</label><textarea id="appointment-notes" name="notes" placeholder="Informações para o atendimento">${escapeHtml(editing?.notes || '')}</textarea></div><div class="form-actions"><button class="load-btn" type="submit">${editing ? 'Salvar alterações' : 'Agendar'}</button>${editing ? '<button class="action-btn outline" type="button" data-action="cancel-appointment-edit">Cancelar edição</button>' : ''}</div></form></section><section class="page-panel"><h2>Resumo</h2><p class="muted">${appointments.length} agendamento(s) cadastrado(s).</p><p class="muted">Os dados são protegidos pelo token da sessão em cada chamada.</p></section></div><section class="page-panel"><div class="toolbar" style="justify-content:space-between"><h2>Todos os agendamentos</h2><span class="muted">${appointments.length} itens</span></div>${appointmentRows(appointments)}</section>`);
}

async function proceduresView() {
  const procedures = apiList(await api('/api/procedures'));
  state.cache.procedures = procedures;
  const editing = state.cache.procedure;
  const title = editing ? 'Editar procedimento' : 'Novo procedimento';
  return professionalLayout('/pro/procedimentos', 'Procedimentos', `<section class="page-header"><h1 class="section-title">Procedimentos</h1><p class="page-intro">Mantenha o catálogo clínico atualizado.</p></section><div class="two-columns"><section class="page-panel"><h2>${title}</h2><form data-form="procedure" data-id="${editing ? Number(editing.id) : ''}"><div class="form-group"><label for="procedure-name">Nome</label><input id="procedure-name" name="name" required value="${escapeHtml(editing?.name || '')}"></div><div class="form-group"><label for="procedure-category">Categoria</label><input id="procedure-category" name="category" value="${escapeHtml(editing?.category || '')}"></div><div class="form-group"><label for="procedure-description">Descrição</label><textarea id="procedure-description" name="description">${escapeHtml(editing?.description || '')}</textarea></div><div class="two-columns"><div class="form-group"><label for="procedure-price">Valor base</label><input id="procedure-price" name="base_price" type="number" min="0" step="0.01" value="${escapeHtml(editing?.base_price ?? '')}"></div><div class="form-group"><label for="procedure-duration">Duração (min.)</label><input id="procedure-duration" name="duration_minutes" type="number" min="1" value="${escapeHtml(editing?.duration_minutes ?? 30)}"></div></div><div class="form-actions"><button class="load-btn" type="submit">${editing ? 'Salvar alterações' : 'Adicionar procedimento'}</button>${editing ? '<button class="action-btn outline" type="button" data-action="cancel-procedure-edit">Cancelar edição</button>' : ''}</div></form></section><section class="page-panel"><h2>Catálogo</h2>${procedures.length ? `<ul class="data-list">${procedures.map((procedure) => `<li><div class="data-row-main"><strong>${escapeHtml(procedure.name)}</strong><span>${escapeHtml(procedure.category || 'Sem categoria')} · ${Number(procedure.duration_minutes || 0)} min · R$ ${Number(procedure.base_price || 0).toFixed(2)}</span></div><div class="toolbar"><button class="action-btn outline" data-action="edit-procedure" data-id="${Number(procedure.id)}">Editar</button><button class="action-btn outline danger-button" data-action="delete-procedure" data-id="${Number(procedure.id)}">Excluir</button></div></li>`).join('')}</ul>` : emptyState('Nenhum procedimento cadastrado.')}</section></div>`);
}

async function patientsView() {
  const patients = apiList(await api('/api/patients'));
  state.cache.patients = patients;
  const searchResults = state.cache.patientSearch;
  return professionalLayout('/pro/pacientes', 'Pacientes', `<section class="page-header"><h1 class="section-title">Pacientes</h1><p class="page-intro">Cadastre pacientes e acesse seus prontuários clínicos.</p></section><div class="two-columns"><section class="page-panel"><h2>Novo paciente</h2><form data-form="patient"><div class="form-group"><label for="patient-name">Nome</label><input id="patient-name" name="name" required></div><div class="two-columns"><div class="form-group"><label for="patient-cpf">CPF</label><input id="patient-cpf" name="cpf" required></div><div class="form-group"><label for="patient-phone">Telefone</label><input id="patient-phone" name="phone" required></div></div><div class="two-columns"><div class="form-group"><label for="patient-email">E-mail</label><input id="patient-email" name="email" type="email"></div><div class="form-group"><label for="patient-birthdate">Nascimento</label><input id="patient-birthdate" name="birth_date" type="date"></div></div><div class="form-group"><label for="patient-allergies">Alergias ou restrições</label><textarea id="patient-allergies" name="allergies"></textarea></div><div class="form-group"><label for="patient-notes">Observações iniciais</label><textarea id="patient-notes" name="notes"></textarea></div><button class="load-btn" type="submit">Cadastrar paciente</button></form></section><section class="page-panel"><h2>Buscar</h2><form data-form="patient-search" class="toolbar"><input aria-label="Buscar paciente" name="query" placeholder="Nome, CPF ou telefone"><button class="action-btn outline" type="submit">Buscar</button></form>${searchResults.length ? `<ul class="data-list">${searchResults.map((patient) => `<li><div class="data-row-main"><strong>${escapeHtml(patient.name)}</strong><span>${escapeHtml(patient.phone || patient.email || '')}</span></div><a class="action-btn outline" href="/pro/pacientes/prontuario/${Number(patient.id)}" data-nav>Prontuário</a></li>`).join('')}</ul>` : '<p class="muted">Use a busca para localizar um paciente específico.</p>'}</section></div><section class="page-panel"><div class="toolbar" style="justify-content:space-between"><h2>Pacientes cadastrados</h2><span class="muted">${patients.length} itens</span></div>${patients.length ? `<ul class="data-list">${patients.map((patient) => `<li><div class="data-row-main"><strong>${escapeHtml(patient.name || 'Paciente')}</strong><span>${escapeHtml(patient.cpf || 'CPF não informado')} · ${escapeHtml(patient.phone || 'Telefone não informado')}</span></div><a class="action-btn outline" href="/pro/pacientes/prontuario/${Number(patient.id)}" data-nav>Abrir prontuário</a></li>`).join('')}</ul>` : emptyState('Nenhum paciente cadastrado.')}</section>`);
}

async function patientRecordView(patientId) {
  const [patientPayload, recordPayload] = await Promise.all([
    api(`/api/patients/${patientId}`),
    api(`/api/patients/${patientId}/records`),
  ]);
  const patient = patientPayload.data || patientPayload;
  const records = apiList(recordPayload);
  return professionalLayout('/pro/pacientes', 'Prontuário', `<section class="page-header"><a class="page-link" href="/pro/pacientes" data-nav>← Voltar aos pacientes</a><h1 class="section-title">${escapeHtml(patient.name || 'Paciente')}</h1><p class="page-intro">${escapeHtml(patient.cpf || 'CPF não informado')} · ${escapeHtml(patient.phone || 'Telefone não informado')}</p></section><div class="two-columns"><section class="page-panel"><h2>Novo registro clínico</h2><form data-form="medical-record" data-patient-id="${Number(patient.id)}"><div class="form-group"><label for="record-notes">Evolução / notas clínicas</label><textarea id="record-notes" name="clinical_notes" required></textarea></div><div class="form-group"><label for="record-prescription">Prescrição ou orientação</label><textarea id="record-prescription" name="prescription"></textarea></div><button class="load-btn" type="submit">Salvar registro</button></form></section><section class="page-panel"><h2>Informações</h2><p class="muted"><strong>E-mail:</strong> ${escapeHtml(patient.email || 'Não informado')}</p><p class="muted"><strong>Nascimento:</strong> ${escapeHtml(patient.birth_date || 'Não informado')}</p></section></div><section class="page-panel"><h2>Histórico clínico</h2>${records.length ? `<ul class="data-list">${records.map((record) => `<li><div class="data-row-main"><strong>${formatDateTime(record.created_at)}</strong><span>${escapeHtml(record.clinical_notes || 'Sem notas clínicas')}</span>${record.prescription ? `<span>Prescrição: ${escapeHtml(record.prescription)}</span>` : ''}</div></li>`).join('')}</ul>` : emptyState('Ainda não há registros clínicos para este paciente.')}</section>`);
}

async function settingsView() {
  const user = state.user || await loadCurrentUser();
  return professionalLayout('/configuracoes', 'Configurações', `<section class="page-header"><h1 class="section-title">Configurações</h1><p class="page-intro">Atualize seus dados de acesso. Dados clínicos do profissional exigem uma API própria e não são simulados.</p></section><div class="two-columns"><section class="page-panel"><h2>Meu perfil</h2><form data-form="profile"><div class="form-group"><label for="profile-name">Nome</label><input id="profile-name" name="name" required value="${escapeHtml(user.name || '')}"></div><div class="form-group"><label for="profile-email">E-mail</label><input id="profile-email" name="email" type="email" required value="${escapeHtml(user.email || '')}"></div><button class="load-btn" type="submit">Salvar perfil</button></form></section><section class="page-panel"><h2>Alterar senha</h2><form data-form="password"><div class="form-group"><label for="current-password">Senha atual</label><input id="current-password" name="current_password" type="password" required autocomplete="current-password"></div><div class="form-group"><label for="new-password">Nova senha</label><input id="new-password" name="password" type="password" required minlength="8" autocomplete="new-password"></div><div class="form-group"><label for="new-password-confirmation">Confirme a nova senha</label><input id="new-password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div><button class="load-btn" type="submit">Atualizar senha</button></form></section></div>`);
}

async function patientDashboardView() {
  const appointments = apiList(await api('/api/patient/appointments'));
  state.cache.patientAppointments = appointments;
  return patientLayout('Início', `<section class="page-header"><h1 class="section-title">Meus agendamentos</h1><p class="page-intro">Acompanhe seus próximos atendimentos e o histórico da clínica.</p></section><div class="two-columns"><section class="page-panel"><h2>Próximos atendimentos</h2>${appointmentRows(appointments, { patient: true, emptyMessage: 'Você ainda não possui agendamentos.' })}</section><aside class="page-panel"><h2>Atalhos</h2><p class="muted">Encontre um profissional, consulte seu prontuário ou atualize seus dados.</p><div class="action-row"><a class="load-btn" href="/paciente/busca" data-nav>Buscar profissional</a><a class="action-btn outline" href="/paciente/prontuario" data-nav>Ver prontuário</a></div></aside></div>`);
}

async function patientSearchView() {
  const results = state.cache.professionalSearch || [];
  const selected = state.cache.selectedProfessional;
  return patientLayout('Buscar profissional', `<section class="page-header"><h1 class="section-title">Encontrar profissional</h1><p class="page-intro">Pesquise por nome, especialidade, cidade ou registro profissional.</p></section><section class="page-panel"><form data-form="professional-search" class="toolbar"><label class="form-group" style="flex:1; margin:0"><span class="sr-only">Buscar profissional</span><input name="query" required minlength="2" placeholder="Ex.: dermatologista, São Paulo ou nome"></label><button class="load-btn" type="submit">Buscar</button></form>${results.length ? `<ul class="data-list">${results.map((professional) => `<li><div class="data-row-main"><strong>${escapeHtml(professional.name)}</strong><span>${escapeHtml(professional.specialty || 'Especialidade não informada')} · ${escapeHtml(professional.city || 'Cidade não informada')}</span></div><button class="action-btn outline" data-action="select-professional" data-id="${Number(professional.id)}">Agendar</button></li>`).join('')}</ul>` : '<p class="muted" style="margin-top:1rem">Digite ao menos dois caracteres para pesquisar.</p>'}</section>${selected ? `<section class="page-panel"><div class="toolbar" style="justify-content:space-between"><div><h2>Agendar com ${escapeHtml(selected.name)}</h2><p class="muted">${escapeHtml(selected.specialty || '')}</p></div><button class="action-btn outline" data-action="clear-professional">Trocar profissional</button></div><form data-form="patient-booking"><div class="two-columns"><div class="form-group"><label for="booking-at">Data e horário</label><input id="booking-at" name="scheduled_at" type="datetime-local" required></div><div class="form-group"><label for="booking-notes">Observações</label><input id="booking-notes" name="notes" placeholder="Opcional"></div></div><button class="load-btn" type="submit">Solicitar agendamento</button></form></section>` : ''}`);
}

async function patientRecordsView() {
  const records = apiList(await api('/api/patient/medical-records'));
  return patientLayout('Prontuário', `<section class="page-header"><h1 class="section-title">Meu prontuário</h1><p class="page-intro">Registros clínicos disponibilizados pelo profissional responsável.</p></section><section class="page-panel">${records.length ? `<ul class="data-list">${records.map((record) => `<li><div class="data-row-main"><strong>${formatDateTime(record.created_at)}</strong><span>${escapeHtml(record.clinical_notes || 'Sem notas clínicas')}</span>${record.prescription ? `<span>Prescrição: ${escapeHtml(record.prescription)}</span>` : ''}</div></li>`).join('')}</ul>` : emptyState('Nenhum registro clínico está disponível.')}</section>`);
}

async function patientProfileView() {
  const user = state.user || await loadCurrentUser();
  const patient = user.patient || {};
  return patientLayout('Meu perfil', `<section class="page-header"><h1 class="section-title">Meu perfil</h1><p class="page-intro">Mantenha seus dados de contato atualizados.</p></section><div class="two-columns"><section class="page-panel"><h2>Dados pessoais</h2><form data-form="profile"><div class="form-group"><label for="patient-profile-name">Nome</label><input id="patient-profile-name" name="name" required value="${escapeHtml(user.name || '')}"></div><div class="form-group"><label for="patient-profile-email">E-mail</label><input id="patient-profile-email" name="email" type="email" required value="${escapeHtml(user.email || '')}"></div><div class="two-columns"><div class="form-group"><label for="patient-profile-phone">Telefone</label><input id="patient-profile-phone" name="phone" value="${escapeHtml(patient.phone || '')}"></div><div class="form-group"><label for="patient-profile-cpf">CPF</label><input id="patient-profile-cpf" name="cpf" value="${escapeHtml(patient.cpf || '')}"></div></div><div class="form-group"><label for="patient-profile-birth">Nascimento</label><input id="patient-profile-birth" name="birth_date" type="date" value="${escapeHtml(patient.birth_date || '')}"></div><button class="load-btn" type="submit">Salvar dados</button></form></section><section class="page-panel"><h2>Senha</h2><form data-form="password"><div class="form-group"><label for="patient-current-password">Senha atual</label><input id="patient-current-password" name="current_password" type="password" required autocomplete="current-password"></div><div class="form-group"><label for="patient-new-password">Nova senha</label><input id="patient-new-password" name="password" type="password" required minlength="8" autocomplete="new-password"></div><div class="form-group"><label for="patient-password-confirmation">Confirme a nova senha</label><input id="patient-password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div><button class="load-btn" type="submit">Atualizar senha</button></form></section></div>`);
}

function activationView() {
  const parameters = new URLSearchParams(location.search);
  const email = parameters.get('email') || '';
  const token = parameters.get('token') || '';
  if (!email || !token) {
    return simpleView('Link de ativação incompleto', 'Use o link integral enviado pela clínica para ativar a conta.', 'ph-warning-circle');
  }
  return authLayout(`<header class="auth-header" style="text-align:center; margin-bottom:1.5rem"><p class="brand-logo" style="justify-content:center">Nüva<span class="brand-dot">.</span></p><h1>Ative sua conta</h1><p class="auth-subtitle">Crie uma senha segura para acessar seu portal.</p></header><form data-form="activation"><input type="hidden" name="email" value="${escapeHtml(email)}"><input type="hidden" name="token" value="${escapeHtml(token)}"><div class="form-group"><label for="activation-password">Nova senha</label><input id="activation-password" name="password" type="password" required minlength="8" autocomplete="new-password"></div><div class="form-group"><label for="activation-confirmation">Confirme a senha</label><input id="activation-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div><button class="load-btn" type="submit">Ativar conta</button></form>`);
}

function notFoundView() {
  return simpleView('Página não encontrada', 'A URL informada não existe no portal.', 'ph-warning-circle');
}

async function render() {
  const renderId = ++state.renderId;
  const route = routeForPath(location.pathname);

  if (route.role && !state.token) {
    navigate('/login', true);
    return;
  }

  if (route.role && state.token) {
    try {
      await loadCurrentUser();
    } catch (error) {
      if (renderId === state.renderId && error.status !== 401) {
        app.innerHTML = simpleView('Não foi possível abrir o portal', error.message, 'ph-warning');
      }
      return;
    }

    if (renderId !== state.renderId) return;

    if (!['patient', 'professional'].includes(state.user?.role)) {
      app.innerHTML = authLayout(`<header class="auth-header" style="text-align:center"><i class="ph ph-warning" style="font-size:3rem;color:var(--md-sys-color-primary)"></i><h1>Perfil sem portal disponível</h1><p class="auth-subtitle">Esta conta não possui uma área de acesso configurada.</p><button class="load-btn" data-action="logout">Sair</button></header>`);
      return;
    }

    if (route.role !== state.user?.role) {
      navigate(currentHome(), true);
      return;
    }
  }

  if (renderId !== state.renderId) return;

  if (isPublicPath(location.pathname) && state.token && state.user) {
    const home = currentHome();
    if (home !== location.pathname) {
      navigate(home, true);
      return;
    }
  }

  try {
    let view;
    switch (route.name) {
      case 'login': view = loginView(); break;
      case 'register': view = registerView(); break;
      case 'recover': view = simpleView('Recuperação de senha', 'A recuperação de senha ainda depende do atendimento da clínica. Entre em contato com o suporte para receber o link seguro.', 'ph-key'); break;
      case 'activate': view = activationView(); break;
      case 'dashboard': view = await professionalDashboardView(); break;
      case 'agenda': view = await agendaView(); break;
      case 'procedures': view = await proceduresView(); break;
      case 'patients': view = await patientsView(); break;
      case 'patient-record': view = await patientRecordView(route.patientId); break;
      case 'settings': view = await settingsView(); break;
      case 'patient-dashboard': view = await patientDashboardView(); break;
      case 'patient-search': view = await patientSearchView(); break;
      case 'patient-records': view = await patientRecordsView(); break;
      case 'patient-profile': view = await patientProfileView(); break;
      default: view = notFoundView();
    }

    if (renderId !== state.renderId) return;
    app.innerHTML = view;
  } catch (error) {
    if (renderId !== state.renderId) return;
    app.innerHTML = simpleView('Não foi possível carregar esta tela', error.message || 'Tente novamente em alguns instantes.', 'ph-warning');
  }

  window.scrollTo({ top: 0, behavior: 'auto' });
}

function togglePassword(targetId) {
  const input = document.getElementById(targetId);
  if (!input) return;
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';
  const icon = document.querySelector(`[data-target="${targetId}"] i`);
  if (icon) icon.className = isPassword ? 'ph ph-eye-slash' : 'ph ph-eye';
}

async function submitLogin(form) {
  const values = Object.fromEntries(new FormData(form));
  const button = form.querySelector('button[type="submit"]');
  button.disabled = true;
  button.textContent = 'Entrando…';
  try {
    const payload = await api('/api/login', { method: 'POST', body: values });
    saveSession(payload);
    navigate(currentHome(), true);
  } catch (error) {
    showToast(error.message, 'error');
  } finally {
    if (button.isConnected) {
      button.disabled = false;
      button.textContent = 'Entrar';
    }
  }
}

async function submitRegister(form) {
  const values = Object.fromEntries(new FormData(form));
  const button = form.querySelector('button[type="submit"]');
  button.disabled = true;
  button.textContent = 'Criando…';
  try {
    const payload = await api('/api/register', { method: 'POST', body: values });
    saveSession(payload);
    navigate(currentHome(), true);
  } catch (error) {
    showToast(error.message, 'error');
  } finally {
    if (button.isConnected) {
      button.disabled = false;
      button.textContent = 'Criar conta';
    }
  }
}

function formValues(form) {
  return Object.fromEntries(new FormData(form).entries());
}

function setFormBusy(form, busy, text) {
  const button = form.querySelector('button[type="submit"]');
  if (!button) return () => {};
  const originalText = button.textContent;
  button.disabled = busy;
  if (busy && text) button.textContent = text;
  return () => {
    if (button.isConnected) {
      button.disabled = false;
      button.textContent = originalText;
    }
  };
}

function errorText(error) {
  const validation = error?.payload?.errors;
  if (validation && typeof validation === 'object') {
    const messages = Object.values(validation).flat().filter(Boolean);
    if (messages.length) return messages.join(' ');
  }
  return error?.message || 'Não foi possível concluir a operação.';
}

async function submitAppointment(form) {
  const restore = setFormBusy(form, true, 'Salvando…');
  const values = formValues(form);
  const appointmentId = Number(form.dataset.id || 0);
  const body = {
    procedure_id: values.procedure_id ? Number(values.procedure_id) : null,
    scheduled_at: String(values.scheduled_at).replace('T', ' '),
    notes: values.notes || null,
  };
  if (!appointmentId) body.patient_id = Number(values.patient_id);
  try {
    await api(appointmentId ? `/api/appointments/${appointmentId}` : '/api/appointments', {
      method: appointmentId ? 'PUT' : 'POST',
      body,
    });
    state.cache.appointment = null;
    showToast(appointmentId ? 'Agendamento atualizado.' : 'Agendamento criado.');
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitProcedure(form) {
  const restore = setFormBusy(form, true, 'Salvando…');
  const values = formValues(form);
  const id = Number(form.dataset.id || 0);
  const body = {
    name: values.name,
    category: values.category || null,
    description: values.description || null,
    base_price: values.base_price === '' ? null : Number(values.base_price),
    duration_minutes: values.duration_minutes === '' ? null : Number(values.duration_minutes),
  };
  try {
    await api(id ? `/api/procedures/${id}` : '/api/procedures', {
      method: id ? 'PUT' : 'POST',
      body,
    });
    state.cache.procedure = null;
    showToast(id ? 'Procedimento atualizado.' : 'Procedimento cadastrado.');
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitPatient(form) {
  const restore = setFormBusy(form, true, 'Cadastrando…');
  const values = formValues(form);
  try {
    await api('/api/patients', { method: 'POST', body: values });
    state.cache.patientSearch = [];
    showToast('Paciente cadastrado.');
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitPatientSearch(form) {
  const values = formValues(form);
  const query = String(values.query || '').trim();
  if (!query) {
    state.cache.patientSearch = [];
    render();
    return;
  }
  const restore = setFormBusy(form, true, 'Buscando…');
  try {
    state.cache.patientSearch = apiList(await api(`/api/patients/search?q=${encodeURIComponent(query)}`));
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitMedicalRecord(form) {
  const restore = setFormBusy(form, true, 'Salvando…');
  const values = formValues(form);
  const patientId = Number(form.dataset.patientId);
  try {
    await api(`/api/patients/${patientId}/records`, {
      method: 'POST',
      body: {
        clinical_notes: values.clinical_notes || null,
        prescription: values.prescription || null,
      },
    });
    showToast('Registro clínico salvo.');
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitProfile(form) {
  const restore = setFormBusy(form, true, 'Salvando…');
  const values = formValues(form);
  try {
    await api('/api/user/profile', { method: 'PUT', body: values });
    await loadCurrentUser();
    showToast('Perfil atualizado.');
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitPassword(form) {
  const restore = setFormBusy(form, true, 'Atualizando…');
  try {
    await api('/api/user/password', { method: 'PUT', body: formValues(form) });
    form.reset();
    showToast('Senha atualizada.');
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitProfessionalSearch(form) {
  const restore = setFormBusy(form, true, 'Buscando…');
  const query = String(formValues(form).query || '').trim();
  try {
    state.cache.professionalSearch = query.length >= 2
      ? apiList(await api(`/api/professionals/search?q=${encodeURIComponent(query)}`))
      : [];
    state.cache.selectedProfessional = null;
    render();
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitPatientBooking(form) {
  const professional = state.cache.selectedProfessional;
  if (!professional) {
    showToast('Selecione um profissional antes de agendar.', 'error');
    return;
  }
  const restore = setFormBusy(form, true, 'Enviando…');
  const values = formValues(form);
  try {
    await api('/api/appointments', {
      method: 'POST',
      body: {
        professional_id: Number(professional.id),
        scheduled_at: String(values.scheduled_at).replace('T', ' '),
        notes: values.notes || null,
      },
    });
    state.cache.selectedProfessional = null;
    showToast('Solicitação de agendamento enviada.');
    navigate('/paciente', true);
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function submitActivation(form) {
  const restore = setFormBusy(form, true, 'Ativando…');
  try {
    await api('/api/activate-account', { method: 'POST', body: formValues(form) });
    showToast('Conta ativada. Faça login para continuar.');
    navigate('/login', true);
  } catch (error) {
    showToast(errorText(error), 'error');
  } finally {
    restore();
  }
}

async function handleAction(action, element) {
  const id = Number(element.dataset.id || 0);

  if (action === 'toggle-password') {
    togglePassword(element.dataset.target);
    return;
  }

  if (action === 'logout') {
    await logout();
    return;
  }

  if (action === 'toggle-theme') {
    document.documentElement.toggleAttribute('data-theme', !document.documentElement.hasAttribute('data-theme'));
    localStorage.setItem('nuva_theme', document.documentElement.hasAttribute('data-theme') ? 'dark' : 'light');
    return;
  }

  if (action === 'toggle-sidebar') {
    document.querySelector('.app')?.classList.toggle('sidebar-expanded');
    return;
  }

  if (action === 'edit-appointment') {
    state.cache.appointment = state.cache.appointments?.find((item) => Number(item.id) === id) || null;
    render();
    return;
  }

  if (action === 'cancel-appointment-edit') {
    state.cache.appointment = null;
    render();
    return;
  }

  if (action === 'appointment-status') {
    try {
      await api(`/api/appointments/${id}/status`, { method: 'PATCH', body: { status: element.dataset.status } });
      showToast('Status do agendamento atualizado.');
      render();
    } catch (error) {
      showToast(errorText(error), 'error');
    }
    return;
  }

  if (action === 'delete-appointment') {
    if (!window.confirm('Excluir este agendamento? Esta ação não pode ser desfeita.')) return;
    try {
      await api(`/api/appointments/${id}`, { method: 'DELETE' });
      state.cache.appointment = null;
      showToast('Agendamento excluído.');
      render();
    } catch (error) {
      showToast(errorText(error), 'error');
    }
    return;
  }

  if (action === 'cancel-patient-appointment') {
    if (!window.confirm('Cancelar este agendamento?')) return;
    try {
      await api(`/api/appointments/${id}/status`, { method: 'PATCH', body: { status: 'canceled' } });
      showToast('Agendamento cancelado.');
      render();
    } catch (error) {
      showToast(errorText(error), 'error');
    }
    return;
  }

  if (action === 'edit-procedure') {
    state.cache.procedure = state.cache.procedures?.find((item) => Number(item.id) === id) || null;
    render();
    return;
  }

  if (action === 'cancel-procedure-edit') {
    state.cache.procedure = null;
    render();
    return;
  }

  if (action === 'delete-procedure') {
    if (!window.confirm('Excluir este procedimento?')) return;
    try {
      await api(`/api/procedures/${id}`, { method: 'DELETE' });
      state.cache.procedure = null;
      showToast('Procedimento excluído.');
      render();
    } catch (error) {
      showToast(errorText(error), 'error');
    }
    return;
  }

  if (action === 'select-professional') {
    state.cache.selectedProfessional = state.cache.professionalSearch?.find((item) => Number(item.id) === id) || null;
    render();
    return;
  }

  if (action === 'clear-professional') {
    state.cache.selectedProfessional = null;
    render();
  }
}

async function logout() {
  try {
    if (state.token) await api('/api/logout', { method: 'POST' });
  } catch {
    // A limpeza local ainda é segura se o token já tiver expirado.
  }
  clearSession();
  showToast('Sessão encerrada.');
  navigate('/login', true);
}

document.addEventListener('click', async (event) => {
  const nav = event.target.closest('[data-nav]');
  if (nav) {
    event.preventDefault();
    navigate(nav.dataset.nav || nav.getAttribute('href'));
    return;
  }

  const action = event.target.closest('[data-action]');
  if (!action) return;
  event.preventDefault();
  await handleAction(action.dataset.action, action);
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-form]');
  if (!form) return;
  event.preventDefault();
  const handlers = {
    login: submitLogin,
    register: submitRegister,
    appointment: submitAppointment,
    procedure: submitProcedure,
    patient: submitPatient,
    'patient-search': submitPatientSearch,
    'medical-record': submitMedicalRecord,
    profile: submitProfile,
    password: submitPassword,
    'professional-search': submitProfessionalSearch,
    'patient-booking': submitPatientBooking,
    activation: submitActivation,
  };
  await handlers[form.dataset.form]?.(form);
});

window.addEventListener('popstate', render);

if (localStorage.getItem('nuva_theme') === 'dark') {
  document.documentElement.setAttribute('data-theme', 'dark');
}

render();
