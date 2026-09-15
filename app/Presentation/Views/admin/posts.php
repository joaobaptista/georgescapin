<?php
$pageTitle = 'Artigos do Blog';
$activeTab = 'posts';
$pageActions = '<a href="' . url('/admin/posts/create') . '" class="btn-admin btn-primary"><i data-lucide="plus" size="16"></i> Novo Artigo</a>';
ob_start();
?>

<div class="admin-table-container">
  <?php if (empty($posts)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 35px;">Nenhum artigo cadastrado no blog ainda.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 80px;">Imagem</th>
          <th>Título & Link</th>
          <th>Autor</th>
          <th>Data</th>
          <th>Status</th>
          <th style="text-align: right; width: 80px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p): ?>
          <tr>
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
              <strong style="color: var(--text-primary); font-size: 0.92rem;"><?= htmlspecialchars($p['title']) ?></strong><br>
              <a href="<?= url('/blog/' . $p['slug']) ?>" target="_blank" style="color: var(--pastel-blue-text); font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                /blog/<?= htmlspecialchars($p['slug']) ?> <i data-lucide="external-link" size="12"></i>
              </a>
            </td>
            <td style="color: var(--text-secondary); font-size: 0.85rem;"><?= htmlspecialchars($p['author'] ?? 'Dr. George') ?></td>
            <td style="color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td>
              <span class="badge <?= !empty($p['is_published']) ? 'badge-success' : 'badge-draft' ?>">
                <?= !empty($p['is_published']) ? 'Publicado' : 'Rascunho' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="table-action-dropdown">
                <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                  <i data-lucide="more-horizontal" size="18"></i>
                </button>
                <div class="table-action-menu">
                  <a href="<?= url('/blog/' . $p['slug']) ?>" target="_blank" class="table-action-item">
                    <i data-lucide="eye" size="15"></i> Ver no Blog
                  </a>
                  <a href="<?= url('/admin/posts/edit/' . $p['id']) ?>" class="table-action-item">
                    <i data-lucide="edit-3" size="15"></i> Editar Artigo
                  </a>
                  <div class="table-action-divider"></div>
                  <button type="button" onclick="openConfirmModal({
                    title: 'Excluir Artigo do Blog?',
                    message: 'Deseja realmente excluir a publicação <strong><?= htmlspecialchars(addslashes($p['title'])) ?></strong>? O conteúdo e a imagem de capa serão removidos do site.',
                    actionUrl: '<?= url('/admin/posts/delete/' . $p['id']) ?>',
                    btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Excluir',
                    type: 'danger'
                  })" class="table-action-item danger">
                    <i data-lucide="trash-2" size="15"></i> Excluir Artigo
                  </button>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?= render_pagination($currentPage, $totalPages, url('/admin/posts'), 'page') ?>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
