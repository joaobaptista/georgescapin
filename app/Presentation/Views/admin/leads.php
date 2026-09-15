<?php
$pageTitle = 'Leads';
$activeTab = 'leads';

$currentTab = $currentTab ?? ($_GET['tab'] ?? 'contacts');
$searchQuery = ($currentTab === 'contacts') ? ($searchContacts ?? '') : ($searchNews ?? '');

if ($currentTab === 'contacts') {
    $pageActions = !empty($contacts) || !empty($searchContacts) 
        ? '<a href="' . url('/admin/leads/export') . '" class="btn-admin btn-secondary"><i data-lucide="download" size="16"></i> Exportar Leads (.CSV)</a>' 
        : '';
} else {
    $pageActions = !empty($subscribers) || !empty($searchNews)
        ? '<a href="' . url('/admin/newsletter/export') . '" class="btn-admin btn-secondary"><i data-lucide="download" size="16"></i> Exportar E-mails (.CSV)</a>' 
        : '';
}

ob_start();
?>

<!-- NAVEGAÇÃO POR ABAS (TABS) FORA DA ÁREA DA TABELA -->
<div class="admin-tabs-nav">
  <a href="<?= url('/admin/leads?tab=contacts' . (!empty($searchContacts) ? '&q_contacts=' . urlencode($searchContacts) : '')) ?>" class="admin-tab-btn <?= $currentTab === 'contacts' ? 'active' : '' ?>">
    <i data-lucide="message-square" size="18"></i>
    <span>Mensagens de Contato</span>
    <span class="tab-badge <?= !empty($newLeadsCount) && $newLeadsCount > 0 ? 'badge-amber' : 'badge-neutral' ?>">
      <?= $contactsTotal ?>
    </span>
  </a>
  <a href="<?= url('/admin/leads?tab=newsletter' . (!empty($searchNews) ? '&q_news=' . urlencode($searchNews) : '')) ?>" class="admin-tab-btn <?= $currentTab === 'newsletter' ? 'active' : '' ?>">
    <i data-lucide="mail" size="18"></i>
    <span>Inscritos na Newsletter</span>
    <span class="tab-badge badge-neutral">
      <?= $newsTotal ?>
    </span>
  </a>
</div>

<!-- BARRA DE FILTRO / BUSCA FORA DA ÁREA DA TABELA -->
<div class="admin-filter-bar">
  <form method="GET" action="<?= url('/admin/leads') ?>" class="admin-search-form">
    <input type="hidden" name="tab" value="<?= $currentTab ?>">
    <div class="admin-search-input-wrapper">
      <input type="text" name="<?= $currentTab === 'contacts' ? 'q_contacts' : 'q_news' ?>" class="admin-search-input" placeholder="Buscar" value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
      <?php if (!empty($searchQuery)): ?>
        <a href="<?= url('/admin/leads?tab=' . $currentTab) ?>" class="admin-search-clear" title="Limpar busca">
          <i data-lucide="x" size="14"></i>
        </a>
      <?php endif; ?>
      <button type="submit" class="admin-search-btn-icon" title="Buscar">
        <i data-lucide="search" size="16"></i>
      </button>
    </div>
  </form>

  <?php if (!empty($searchQuery)): ?>
    <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
      <span>Resultados para "<strong><?= htmlspecialchars($searchQuery) ?></strong>" (<?= $currentTab === 'contacts' ? $contactsTotal : $newsTotal ?> encontrado<?= ($currentTab === 'contacts' ? $contactsTotal : $newsTotal) === 1 ? '' : 's' ?>)</span>
      <a href="<?= url('/admin/leads?tab=' . $currentTab) ?>" class="btn-admin btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.75rem;">
        Limpar filtro
      </a>
    </div>
  <?php endif; ?>
</div>

<?php if ($currentTab === 'contacts'): ?>
  <!-- ABA 1: MENSAGENS DE CONTATO (TABELA DIRETA, SEM CARD DUPLO) -->
  <div class="admin-table-container">
    <?php if (empty($contacts)): ?>
      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 60px 20px; color: var(--text-muted);">
        <i data-lucide="inbox" size="36" style="margin-bottom: 12px; color: var(--text-muted); opacity: 0.6;"></i>
        <p style="margin: 0; font-size: 0.95rem;">
          <?= !empty($searchContacts) ? 'Nenhuma mensagem encontrada para o termo pesquisado.' : 'Nenhuma mensagem registrada até o momento.' ?>
        </p>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Data / Hora</th>
            <th>Nome</th>
            <th>Telefone / WhatsApp</th>
            <th>Mensagem</th>
            <th>Status</th>
            <th>Alterar Status</th>
            <th style="text-align: right; width: 80px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contacts as $c): ?>
            <?php
              $leadData = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'telefone' => $c['telefone'],
                'mensagem' => $c['mensagem'],
                'status' => $c['status'],
                'created_at' => $c['created_at'],
                'formatted_date' => date('d/m/Y \à\s H:i', strtotime($c['created_at']))
              ];
              $leadJson = htmlspecialchars(json_encode($leadData), ENT_QUOTES, 'UTF-8');
            ?>
            <tr class="lead-clickable-row" onclick="handleLeadRowClick(event, <?= $leadJson ?>)" style="cursor: pointer;" title="Clique para abrir os detalhes e ler a mensagem">
              <td style="color: var(--text-muted); font-size: 0.82rem; white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
              <td>
                <strong style="color: var(--text-primary); display: inline-flex; align-items: center; gap: 6px;">
                  <?= htmlspecialchars($c['nome']) ?>
                </strong>
              </td>
              <td style="color: var(--text-secondary);"><?= htmlspecialchars($c['telefone']) ?></td>
              <td style="max-width: 280px; color: var(--text-secondary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <?= htmlspecialchars(mb_strimwidth($c['mensagem'], 0, 75, '...')) ?>
              </td>
              <td>
                <span class="badge <?= $c['status'] === 'novo' ? 'badge-warning' : ($c['status'] === 'atendido' ? 'badge-success' : 'badge-muted') ?>">
                  <?= ucfirst($c['status']) ?>
                </span>
              </td>
              <td>
                <form method="POST" action="<?= url('/admin/leads/status/' . $c['id']) ?>" style="display: flex; gap: 5px;" onclick="event.stopPropagation();">
                  <?= csrf_field() ?>
                  <select name="status" onchange="this.form.submit()" style="background: var(--bg-surface-hover); border: 1px solid var(--border-light); color: var(--text-primary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.82rem; cursor: pointer;">
                    <option value="novo" <?= $c['status'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                    <option value="atendido" <?= $c['status'] === 'atendido' ? 'selected' : '' ?>>Atendido</option>
                    <option value="arquivado" <?= $c['status'] === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                  </select>
                </form>
              </td>
              <td style="text-align: right;" onclick="event.stopPropagation();">
                <div class="table-action-dropdown">
                  <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                    <i data-lucide="more-horizontal" size="18"></i>
                  </button>
                  <div class="table-action-menu">
                    <button type="button" onclick="openLeadModal(<?= $leadJson ?>)" class="table-action-item">
                      <i data-lucide="eye" size="15"></i> Ver Detalhes / Mensagem
                    </button>
                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $c['telefone']) ?>?text=Olá%20<?= urlencode($c['nome']) ?>,%20recebemos%20sua%20mensagem%20no%20site%20do%20Dr.%20George%20Scapin." target="_blank" class="table-action-item whatsapp">
                      <i data-lucide="message-circle" size="15"></i> Falar no WhatsApp
                    </a>
                    <div class="table-action-divider"></div>
                    <form method="POST" action="<?= url('/admin/leads/status/' . $c['id']) ?>" style="margin: 0;">
                      <?= csrf_field() ?>
                      <?php if ($c['status'] !== 'atendido'): ?>
                        <button type="submit" name="status" value="atendido" class="table-action-item">
                          <i data-lucide="check-circle" size="15"></i> Marcar como Atendido
                        </button>
                      <?php endif; ?>
                      <?php if ($c['status'] !== 'novo'): ?>
                        <button type="submit" name="status" value="novo" class="table-action-item">
                          <i data-lucide="clock" size="15"></i> Marcar como Novo
                        </button>
                      <?php endif; ?>
                      <?php if ($c['status'] !== 'arquivado'): ?>
                        <button type="submit" name="status" value="arquivado" class="table-action-item">
                          <i data-lucide="archive" size="15"></i> Arquivar Lead
                        </button>
                      <?php endif; ?>
                    </form>
                    <div class="table-action-divider"></div>
                    <form method="POST" action="<?= url('/admin/leads/delete/' . $c['id']) ?>" onsubmit="return confirm('Tem certeza que deseja excluir esta mensagem de lead permanentemente?');" style="margin: 0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="table-action-item danger">
                        <i data-lucide="trash-2" size="15"></i> Excluir Mensagem
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?= render_pagination($contactsCurrentPage, $contactsTotalPages, url('/admin/leads'), 'contacts_page') ?>
    <?php endif; ?>
  </div>

<?php else: ?>
  <!-- ABA 2: INSCRITOS NA NEWSLETTER (TABELA DIRETA, SEM CARD DUPLO) -->
  <div class="admin-table-container">
    <?php if (empty($subscribers)): ?>
      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; padding: 60px 20px; color: var(--text-muted);">
        <i data-lucide="mail" size="36" style="margin-bottom: 12px; color: var(--text-muted); opacity: 0.6;"></i>
        <p style="margin: 0; font-size: 0.95rem;">
          <?= !empty($searchNews) ? 'Nenhum e-mail encontrado para o termo pesquisado.' : 'Nenhum assinante cadastrado até o momento.' ?>
        </p>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 70px;">ID</th>
            <th>E-mail</th>
            <th>Data de Inscrição</th>
            <th>Status</th>
            <th style="text-align: right; width: 80px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subscribers as $s): ?>
            <tr>
              <td style="color: var(--text-muted); font-weight: 600;">#<?= $s['id'] ?></td>
              <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($s['email']) ?></strong></td>
              <td style="color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
              <td><span class="badge badge-success"><?= ucfirst($s['status']) ?></span></td>
              <td style="text-align: right;">
                <div class="table-action-dropdown">
                  <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                    <i data-lucide="more-horizontal" size="18"></i>
                  </button>
                  <div class="table-action-menu">
                    <a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="table-action-item">
                      <i data-lucide="mail" size="15"></i> Enviar E-mail
                    </a>
                    <div class="table-action-divider"></div>
                    <form method="POST" action="<?= url('/admin/newsletter/delete/' . $s['id']) ?>" onsubmit="return confirm('Tem certeza que deseja excluir este e-mail da newsletter?');" style="margin: 0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="table-action-item danger">
                        <i data-lucide="trash-2" size="15"></i> Excluir E-mail
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?= render_pagination($newsCurrentPage, $newsTotalPages, url('/admin/leads'), 'news_page') ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- MODAL DE DETALHES DO CONTATO / LEITURA DE MENSAGEM -->
<div id="leadDetailsModal" style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.2s ease;">
  <div id="leadDetailsCard" style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); width: 92%; max-width: 580px; box-shadow: var(--shadow-lg); overflow: hidden; display: flex; flex-direction: column; transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);">
    
    <!-- Header do Modal -->
    <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between; background: var(--bg-surface-hover);">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--pastel-blue-bg); color: var(--pastel-blue-accent); display: flex; align-items: center; justify-content: center;">
          <i data-lucide="message-square" size="20"></i>
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">Detalhes do Contato</h3>
          <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 2px;">
            <i data-lucide="calendar" size="13"></i>
            <span id="modalLeadDate"></span>
          </div>
        </div>
      </div>
      <button type="button" onclick="closeLeadModal()" style="background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;" title="Fechar">
        <i data-lucide="x" size="20"></i>
      </button>
    </div>

    <!-- Corpo do Modal -->
    <div style="padding: 24px; display: flex; flex-direction: column; gap: 18px; max-height: calc(85vh - 140px); overflow-y: auto;">
      
      <!-- Grid de Informações -->
      <div class="admin-grid-2col" style="gap: 14px;">
        <div style="background: var(--bg-surface-hover); padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.04em; display: block; margin-bottom: 4px;">Nome do Lead</span>
          <strong id="modalLeadName" style="font-size: 0.98rem; color: var(--text-primary); word-break: break-word;"></strong>
        </div>

        <div style="background: var(--bg-surface-hover); padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.04em; display: block; margin-bottom: 4px;">Telefone / WhatsApp</span>
          <strong id="modalLeadPhone" style="font-size: 0.98rem; color: var(--text-primary); word-break: break-all;"></strong>
        </div>
      </div>

      <!-- Status Atual com Alteração Rápida -->
      <div style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-surface-hover); padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Status do Atendimento:</span>
          <span id="modalLeadStatusBadge" class="badge badge-warning">Novo</span>
        </div>
        
        <form id="modalLeadStatusForm" method="POST" action="" style="display: flex; align-items: center; gap: 8px; margin: 0;">
          <?= csrf_field() ?>
          <label for="modalLeadStatusSelect" style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Alterar:</label>
          <select id="modalLeadStatusSelect" name="status" onchange="this.form.submit()" style="background: var(--bg-surface); border: 1px solid var(--border-light); color: var(--text-primary); padding: 5px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; cursor: pointer;">
            <option value="novo">Novo</option>
            <option value="atendido">Atendido</option>
            <option value="arquivado">Arquivado</option>
          </select>
        </form>
      </div>

      <!-- Conteúdo da Mensagem -->
      <div>
        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
          Mensagem Enviada:
        </label>
        <div id="modalLeadMessage" style="background: var(--bg-canvas); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 16px; font-size: 0.92rem; line-height: 1.6; color: var(--text-secondary); white-space: pre-wrap; word-break: break-word; min-height: 100px; max-height: 220px; overflow-y: auto;"></div>
      </div>
    </div>

    <!-- Rodapé do Modal -->
    <div style="padding: 16px 24px; border-top: 1px solid var(--border-subtle); background: var(--bg-surface-hover); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <form id="modalLeadDeleteForm" method="POST" action="" onsubmit="return confirm('Tem certeza que deseja excluir esta mensagem de lead permanentemente?');" style="margin: 0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn-admin btn-danger btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
          <i data-lucide="trash-2" size="14"></i> Excluir Mensagem
        </button>
      </form>

      <div style="display: flex; align-items: center; gap: 10px;">
        <a id="modalLeadWhatsAppBtn" href="#" target="_blank" class="btn-admin" style="background: #25d366; color: #ffffff; border: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
          <i data-lucide="message-circle" size="16"></i> Falar no WhatsApp
        </a>
        <button type="button" onclick="closeLeadModal()" class="btn-admin btn-secondary">
          Fechar
        </button>
      </div>
    </div>

  </div>
</div>

<script>
function handleLeadRowClick(event, lead) {
  // Ignora se o clique for em formulário, select, link ou menu de ações
  if (event.target.closest('a, button, select, form, .table-action-dropdown')) {
    return;
  }
  openLeadModal(lead);
}

function openLeadModal(lead) {
  const modal = document.getElementById('leadDetailsModal');
  const card = document.getElementById('leadDetailsCard');
  if (!modal || !card) return;

  document.getElementById('modalLeadName').textContent = lead.nome || '';
  document.getElementById('modalLeadPhone').textContent = lead.telefone || '';
  document.getElementById('modalLeadDate').textContent = lead.formatted_date || lead.created_at || '';
  document.getElementById('modalLeadMessage').textContent = lead.mensagem || '';

  // WhatsApp Button
  const cleanPhone = (lead.telefone || '').replace(/\D/g, '');
  const waBtn = document.getElementById('modalLeadWhatsAppBtn');
  if (cleanPhone && waBtn) {
    waBtn.href = `https://wa.me/55${cleanPhone}?text=Olá%20${encodeURIComponent(lead.nome || '')},%20recebemos%20sua%20mensagem%20no%20site%20do%20Dr.%20George%20Scapin.`;
    waBtn.style.display = 'inline-flex';
  } else if (waBtn) {
    waBtn.style.display = 'none';
  }

  // Status Badge
  const statusBadge = document.getElementById('modalLeadStatusBadge');
  if (statusBadge) {
    const st = lead.status || 'novo';
    statusBadge.textContent = st.charAt(0).toUpperCase() + st.slice(1);
    statusBadge.className = 'badge ' + (st === 'novo' ? 'badge-warning' : (st === 'atendido' ? 'badge-success' : 'badge-muted'));
  }

  // Status Form
  const statusSelect = document.getElementById('modalLeadStatusSelect');
  if (statusSelect) {
    statusSelect.value = lead.status || 'novo';
  }
  const statusForm = document.getElementById('modalLeadStatusForm');
  if (statusForm) {
    statusForm.action = `<?= url('/admin/leads/status/') ?>/${lead.id}`;
  }

  // Delete Form
  const deleteForm = document.getElementById('modalLeadDeleteForm');
  if (deleteForm) {
    deleteForm.action = `<?= url('/admin/leads/delete/') ?>/${lead.id}`;
  }

  modal.style.display = 'flex';
  setTimeout(() => {
    modal.style.opacity = '1';
    card.style.transform = 'scale(1)';
  }, 10);

  if (window.lucide) {
    lucide.createIcons();
  }
}

function closeLeadModal() {
  const modal = document.getElementById('leadDetailsModal');
  const card = document.getElementById('leadDetailsCard');
  if (!modal || !card) return;

  modal.style.opacity = '0';
  card.style.transform = 'scale(0.95)';
  setTimeout(() => {
    modal.style.display = 'none';
  }, 200);
}

document.getElementById('leadDetailsModal')?.addEventListener('click', (e) => {
  if (e.target.id === 'leadDetailsModal') {
    closeLeadModal();
  }
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeLeadModal();
  }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
