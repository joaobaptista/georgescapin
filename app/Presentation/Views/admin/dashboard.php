<?php
$pageTitle = 'Dashboard';
$activeTab = 'dashboard';
ob_start();
?>

<!-- SEÇÃO: MÓDULOS & APPS (Cards Estilo Jira / Atlassian) -->
<div class="atlassian-cards-grid" style="margin-top: 0;">
  <a href="<?= url('/admin/procedures') ?>" class="atlassian-app-card">
    <div class="atlassian-app-icon" style="background: var(--pastel-blue-bg); color: var(--pastel-blue-accent);">
      <i data-lucide="sparkles" size="20"></i>
    </div>
    <div class="atlassian-app-info">
      <div class="atlassian-app-title">Procedimentos</div>
      <div class="atlassian-app-meta"><?= count($procedures) ?> tratamentos ativos</div>
    </div>
  </a>

  <a href="<?= url('/admin/leads') ?>" class="atlassian-app-card">
    <div class="atlassian-app-icon" style="background: var(--pastel-green-bg); color: var(--pastel-green-accent);">
      <i data-lucide="message-square" size="20"></i>
    </div>
    <div class="atlassian-app-info">
      <div class="atlassian-app-title">Leads & Contatos</div>
      <div class="atlassian-app-meta"><?= count($contacts) ?> mensagens recebidas</div>
    </div>
  </a>

  <a href="<?= url('/admin/pages') ?>" class="atlassian-app-card">
    <div class="atlassian-app-icon" style="background: var(--pastel-purple-bg); color: var(--pastel-purple-accent);">
      <i data-lucide="layout-template" size="20"></i>
    </div>
    <div class="atlassian-app-info">
      <div class="atlassian-app-title">Páginas do Site</div>
      <div class="atlassian-app-meta">Home, Clínica, Harmonização...</div>
    </div>
  </a>

  <a href="<?= url('/admin/posts') ?>" class="atlassian-app-card">
    <div class="atlassian-app-icon" style="background: var(--pastel-amber-bg); color: var(--pastel-amber-accent);">
      <i data-lucide="newspaper" size="20"></i>
    </div>
    <div class="atlassian-app-info">
      <div class="atlassian-app-title">Artigos do Blog</div>
      <div class="atlassian-app-meta"><?= count($posts ?? []) ?> publicações ativas</div>
    </div>
  </a>

  <a href="<?= url('/admin/leads') ?>" class="atlassian-app-card">
    <div class="atlassian-app-icon" style="background: var(--pastel-blue-bg); color: var(--pastel-blue-accent);">
      <i data-lucide="mail" size="20"></i>
    </div>
    <div class="atlassian-app-info">
      <div class="atlassian-app-title">Newsletter</div>
      <div class="atlassian-app-meta"><?= count($subscribers) ?> assinantes ativos</div>
    </div>
  </a>
</div>

<!-- ÁREA CINZA / BANNER INFORMATIVO (Padrão Atlassian Jira) -->
<div class="atlassian-section-header">
  <div class="atlassian-section-title">Status do Atendimento</div>
</div>

<div class="atlassian-grey-banner">
  <?php if ($newLeadsCount > 0): ?>
    <i data-lucide="bell" size="20" style="color: var(--pastel-amber-accent);"></i>
    <div style="flex: 1;">
      Você possui <strong><?= $newLeadsCount ?></strong> novo(s) lead(s) de pacientes aguardando retorno.
    </div>
    <a href="<?= url('/admin/leads') ?>" class="btn-primary" style="padding: 6px 14px; font-size: 0.82rem;">
      Atender Leads <i data-lucide="arrow-right" size="14"></i>
    </a>
  <?php else: ?>
    <i data-lucide="check-circle" size="20" style="color: var(--pastel-green-accent);"></i>
    <div style="flex: 1;">
      Tudo em dia! Todas as mensagens e leads de pacientes foram atendidos. Nenhum retorno pendente.
    </div>
  <?php endif; ?>
</div>

<!-- SEÇÃO: ÚLTIMOS CONTATOS -->
<div class="atlassian-section-header">
  <div class="atlassian-section-title">Últimos Contatos Recebidos</div>
  <a href="<?= url('/admin/leads') ?>" class="atlassian-section-link">
    Visualizar todos os contatos <i data-lucide="arrow-right" size="14"></i>
  </a>
</div>

<div class="admin-table-container" style="margin-bottom: 20px;">
  <?php if (empty($contacts)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 35px;">Nenhuma mensagem de contato recebida ainda.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>Mensagem</th>
          <th>Status</th>
          <th style="text-align: right; width: 80px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($contacts, 0, 5) as $c): ?>
          <tr>
            <td style="color: var(--text-muted); font-size: 0.82rem; white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
            <td><strong style="color: var(--text-primary);"><?= htmlspecialchars($c['nome']) ?></strong></td>
            <td style="color: var(--text-secondary);"><?= htmlspecialchars($c['telefone']) ?></td>
            <td style="max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-secondary);"><?= htmlspecialchars($c['mensagem']) ?></td>
            <td>
              <span class="badge <?= $c['status'] === 'novo' ? 'badge-warning' : ($c['status'] === 'atendido' ? 'badge-success' : 'badge-muted') ?>">
                <?= ucfirst($c['status']) ?>
              </span>
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
                  <a href="<?= url('/admin/leads') ?>" class="table-action-item">
                    <i data-lucide="inbox" size="15"></i> Gerenciar Leads
                  </a>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- SEÇÃO: PROCEDIMENTOS CADASTRADOS -->
<div class="atlassian-section-header">
  <div class="atlassian-section-title">Procedimentos Cadastrados</div>
  <a href="<?= url('/admin/procedures/create') ?>" class="btn-primary" style="padding: 6px 14px; font-size: 0.82rem;">
    <i data-lucide="plus" size="14"></i> Novo Procedimento
  </a>
</div>

<div class="admin-table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width: 70px;">Ordem</th>
        <th style="width: 80px;">Imagem</th>
        <th>Título do Procedimento</th>
        <th>Ícone</th>
        <th>Status</th>
        <th style="text-align: right; width: 80px;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($procedures as $p): ?>
        <tr>
          <td style="color: var(--text-muted); font-weight: 600;"><?= $p['sort_order'] ?></td>
          <td>
            <img src="<?= asset($p['image_url'] ?: 'assets/images/botox.png') ?>" style="width: 50px; height: 36px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
          </td>
          <td>
            <a href="<?= url('/admin/procedures/edit/' . $p['id']) ?>" style="color: var(--text-primary); font-weight: 600;">
              <?= htmlspecialchars($p['title']) ?>
            </a>
          </td>
          <td><i data-lucide="<?= htmlspecialchars($p['icon_name'] ?: 'sparkles') ?>" size="18" style="color: var(--pastel-blue-accent);"></i></td>
          <td>
            <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $p['is_active'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td style="text-align: right;">
            <div class="table-action-dropdown">
              <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                <i data-lucide="more-horizontal" size="18"></i>
              </button>
              <div class="table-action-menu">
                <a href="<?= url('/admin/procedures/edit/' . $p['id']) ?>" class="table-action-item">
                  <i data-lucide="edit-3" size="15"></i> Editar Procedimento
                </a>
                <a href="<?= url('/procedimentos#' . $p['slug']) ?>" target="_blank" class="table-action-item">
                  <i data-lucide="eye" size="15"></i> Ver no Site
                </a>
                <div class="table-action-divider"></div>
                <button type="button" onclick="openConfirmModal({
                  title: 'Excluir Procedimento?',
                  message: 'Tem certeza que deseja excluir o tratamento <strong><?= htmlspecialchars(addslashes($p['title'])) ?></strong>? A imagem e todos os dados associados serão removidos permanentemente.',
                  actionUrl: '<?= url('/admin/procedures/delete/' . $p['id']) ?>',
                  btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Excluir',
                  type: 'danger'
                })" class="table-action-item danger">
                  <i data-lucide="trash-2" size="15"></i> Excluir Procedimento
                </button>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
