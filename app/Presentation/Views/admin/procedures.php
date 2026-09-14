<?php
$pageTitle = 'Gerenciar Tratamentos';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Todos os Tratamentos</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Gerencie os procedimentos exibidos na Home e na página de Tratamentos</p>
    </div>
    <a href="<?= url('/admin/procedures/create') ?>" class="btn-primary" style="padding: 10px 20px;">
      <i data-lucide="plus" size="16"></i> Adicionar Tratamento
    </a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Ordem</th>
        <th>Imagem</th>
        <th>Título & Slug</th>
        <th>Descrição Breve</th>
        <th>Status</th>
        <th style="text-align: right;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($procedures as $p): ?>
        <tr>
          <td><strong>#<?= $p['sort_order'] ?></strong></td>
          <td>
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= asset($p['image_url']) ?>" style="width: 65px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); display: block;">
            <?php else: ?>
              <div style="width: 65px; height: 45px; background: rgba(197,160,89,0.08); border: 1px dashed var(--card-border); border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.65rem; text-align: center; gap: 2px;">
                <i data-lucide="image-off" size="14"></i>
                <span>Sem foto</span>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= htmlspecialchars($p['title']) ?></strong><br>
            <small style="color: var(--text-muted); font-size: 0.75rem;">/<?= htmlspecialchars($p['slug']) ?></small>
          </td>
          <td style="max-width: 320px;"><?= htmlspecialchars($p['short_description']) ?></td>
          <td>
            <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $p['is_active'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td style="text-align: right;">
            <div style="display: inline-flex; gap: 10px;">
              <a href="<?= url('/admin/procedures/edit/' . $p['id']) ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem;">
                <i data-lucide="edit" size="14"></i> Editar
              </a>
              <button type="button" onclick="openConfirmModal({
                title: 'Excluir Procedimento?',
                message: 'Tem certeza que deseja excluir o tratamento <strong><?= htmlspecialchars(addslashes($p['title'])) ?></strong>? A imagem e todos os dados associados serão removidos permanentemente.',
                actionUrl: '<?= url('/admin/procedures/delete/' . $p['id']) ?>',
                btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Excluir Procedimento'
              })" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; border-color: #ef4444; color: #ef4444;" title="Excluir Tratamento">
                <i data-lucide="trash-2" size="14"></i>
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?= render_pagination($currentPage, $totalPages, url('/admin/procedures'), 'page') ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
