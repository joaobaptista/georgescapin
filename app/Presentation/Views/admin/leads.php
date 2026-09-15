<?php
$pageTitle = 'Leads & Mensagens';
$activeTab = 'leads';
$pageActions = !empty($contacts) ? '<a href="' . url('/admin/leads/export') . '" class="btn-admin btn-secondary"><i data-lucide="download" size="16"></i> Exportar Leads (.CSV)</a>' : '';
ob_start();
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Mensagens de Contato Recentes</h3>
  </div>

  <div class="admin-table-container">
    <?php if (empty($contacts)): ?>
      <p style="color: var(--text-muted); text-align: center; padding: 35px;">Nenhuma mensagem registrada até o momento.</p>
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
            <tr>
              <td style="color: var(--text-muted); font-size: 0.82rem; white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
              <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($c['nome']) ?></strong></td>
              <td style="color: var(--text-secondary);"><?= htmlspecialchars($c['telefone']) ?></td>
              <td style="max-width: 280px; color: var(--text-secondary);"><?= nl2br(htmlspecialchars($c['mensagem'])) ?></td>
              <td>
                <span class="badge <?= $c['status'] === 'novo' ? 'badge-warning' : ($c['status'] === 'atendido' ? 'badge-success' : 'badge-muted') ?>">
                  <?= ucfirst($c['status']) ?>
                </span>
              </td>
              <td>
                <form method="POST" action="<?= url('/admin/leads/status/' . $c['id']) ?>" style="display: flex; gap: 5px;">
                  <?= csrf_field() ?>
                  <select name="status" onchange="this.form.submit()" style="background: var(--bg-surface-hover); border: 1px solid var(--border-light); color: var(--text-primary); padding: 4px 8px; border-radius: var(--radius-sm); font-size: 0.82rem; cursor: pointer;">
                    <option value="novo" <?= $c['status'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                    <option value="atendido" <?= $c['status'] === 'atendido' ? 'selected' : '' ?>>Atendido</option>
                    <option value="arquivado" <?= $c['status'] === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                  </select>
                </form>
              </td>
              <td style="text-align: right;">
                <div class="table-action-dropdown">
                  <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                    <i data-lucide="more-horizontal" size="18"></i>
                  </button>
                  <div class="table-action-menu">
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
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Inscritos na Newsletter</h3>
    <?php if (!empty($subscribers)): ?>
      <a href="<?= url('/admin/newsletter/export') ?>" class="btn-admin btn-secondary btn-sm">
        <i data-lucide="download" size="14"></i> Exportar E-mails (.CSV)
      </a>
    <?php endif; ?>
  </div>

  <div class="admin-table-container">
    <?php if (empty($subscribers)): ?>
      <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhum assinante cadastrado ainda.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 70px;">ID</th>
            <th>E-mail</th>
            <th>Data de Inscrição</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subscribers as $s): ?>
            <tr>
              <td style="color: var(--text-muted); font-weight: 600;">#<?= $s['id'] ?></td>
              <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($s['email']) ?></strong></td>
              <td style="color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
              <td><span class="badge badge-success"><?= ucfirst($s['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?= render_pagination($newsCurrentPage, $newsTotalPages, url('/admin/leads'), 'news_page') ?>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
