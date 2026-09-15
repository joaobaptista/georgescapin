<?php
$pageTitle = 'Procedimentos';
$activeTab = 'procedures';
$pageActions = '<a href="' . url('/admin/procedures/create') . '" class="btn-admin btn-primary"><i data-lucide="plus" size="16"></i> Adicionar Tratamento</a>';
ob_start();
?>

<div class="admin-table-container">
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width: 70px;">Ordem</th>
        <th style="width: 80px;">Imagem</th>
        <th>Título</th>
        <th>Descrição Breve</th>
        <th>Status</th>
        <th style="text-align: right; width: 80px;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($procedures)): ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 35px; color: var(--text-muted);">
            Nenhum procedimento cadastrado ainda.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($procedures as $p): ?>
          <tr>
            <td style="color: var(--text-muted); font-weight: 600;">#<?= $p['sort_order'] ?></td>
            <td>
              <?php if (!empty($p['image_url'])): ?>
                <img src="<?= asset($p['image_url']) ?>" style="width: 56px; height: 38px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-light); display: block;">
              <?php else: ?>
                <div style="width: 56px; height: 38px; background: var(--bg-surface-hover); border: 1px dashed var(--border-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                  <i data-lucide="image-off" size="14"></i>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= url('/admin/procedures/edit/' . $p['id']) ?>" style="color: var(--text-primary); font-weight: 600;">
                <?= htmlspecialchars($p['title']) ?>
              </a>
            </td>
            <td style="max-width: 320px; color: var(--text-secondary);"><?= htmlspecialchars($p['short_description']) ?></td>
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
                    <i data-lucide="edit-3" size="15"></i> Editar Tratamento
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
                    <i data-lucide="trash-2" size="15"></i> Excluir Tratamento
                  </button>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?= render_pagination($currentPage, $totalPages, url('/admin/procedures'), 'page') ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
