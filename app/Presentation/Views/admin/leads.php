<?php
$pageTitle = 'Leads e Mensagens Recebidas';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Mensagens de Contato</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Pacientes que preencheram o formulário de contato do site</p>
    </div>
    <?php if (!empty($contacts)): ?>
      <a href="<?= url('/admin/leads/export') ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i data-lucide="download" size="16"></i> Exportar Leads (.CSV)
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($contacts)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhuma mensagem registrada até o momento.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Data / Hora</th>
          <th>Nome</th>
          <th>Telefone / WhatsApp</th>
          <th>Mensagem do Paciente</th>
          <th>Status</th>
          <th>Alterar Status</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
          <tr>
            <td style="color: var(--text-muted); font-size: 0.8rem; white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
            <td><?= htmlspecialchars($c['telefone']) ?></td>
            <td style="max-width: 320px;"><?= nl2br(htmlspecialchars($c['mensagem'])) ?></td>
            <td>
              <span class="badge <?= $c['status'] === 'novo' ? 'badge-warning' : ($c['status'] === 'atendido' ? 'badge-success' : 'badge-muted') ?>">
                <?= ucfirst($c['status']) ?>
              </span>
            </td>
            <td>
              <form method="POST" action="<?= url('/admin/leads/status/' . $c['id']) ?>" style="display: flex; gap: 5px;">
                <?= csrf_field() ?>
                <select name="status" onchange="this.form.submit()" style="background: var(--bg-input); border: 1px solid var(--card-border); color: var(--text-main); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                  <option value="novo" <?= $c['status'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                  <option value="atendido" <?= $c['status'] === 'atendido' ? 'selected' : '' ?>>Atendido</option>
                  <option value="arquivado" <?= $c['status'] === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                </select>
              </form>
            </td>
            <td>
              <a href="https://wa.me/55<?= preg_replace('/\D/', '', $c['telefone']) ?>?text=Olá%20<?= urlencode($c['nome']) ?>,%20recebemos%20sua%20mensagem%20no%20site%20do%20Dr.%20George%20Scapin." target="_blank" class="btn-primary" style="padding: 4px 10px; font-size: 0.75rem; background: #25D366; border-color: #25D366; color: #fff;">
                <i data-lucide="message-circle" size="14"></i> Chamar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?= render_pagination($contactsCurrentPage, $contactsTotalPages, url('/admin/leads'), 'contacts_page') ?>
  <?php endif; ?>
</div>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Inscritos na Newsletter</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Lista de e-mails capturados através do rodapé do site</p>
    </div>
    <?php if (!empty($subscribers)): ?>
      <a href="<?= url('/admin/newsletter/export') ?>" class="btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i data-lucide="download" size="16"></i> Exportar E-mails (.CSV)
      </a>
    <?php endif; ?>
  </div>

  <?php if (empty($subscribers)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 20px;">Nenhum assinante cadastrado ainda.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>E-mail</th>
          <th>Data de Inscrição</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subscribers as $s): ?>
          <tr>
            <td>#<?= $s['id'] ?></td>
            <td><strong><?= htmlspecialchars($s['email']) ?></strong></td>
            <td style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
            <td><span class="badge badge-success"><?= ucfirst($s['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?= render_pagination($newsCurrentPage, $newsTotalPages, url('/admin/leads'), 'news_page') ?>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
