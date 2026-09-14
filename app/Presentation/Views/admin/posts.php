<?php
$pageTitle = 'Gerenciar Artigos do Blog';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Todos os Artigos</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Gerencie posts, artigos educativos e novidades do blog</p>
    </div>
    <a href="<?= url('/admin/posts/create') ?>" class="btn-primary" style="padding: 10px 20px;">
      <i data-lucide="plus" size="16"></i> Novo Artigo
    </a>
  </div>

  <?php if (empty($posts)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhum artigo cadastrado no blog ainda.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Imagem</th>
          <th>Título & Slug</th>
          <th>Autor</th>
          <th>Data</th>
          <th>Status</th>
          <th style="text-align: right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p): ?>
          <tr>
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
              <small style="color: var(--text-muted); font-size: 0.75rem;">/blog/<?= htmlspecialchars($p['slug']) ?></small>
            </td>
            <td><?= htmlspecialchars($p['author'] ?? 'Dr. George') ?></td>
            <td style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td>
              <span class="badge <?= $p['is_published'] ? 'badge-success' : 'badge-muted' ?>">
                <?= $p['is_published'] ? 'Publicado' : 'Rascunho' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 10px;">
                <a href="<?= url('/blog/' . $p['slug']) ?>" target="_blank" class="btn-primary" style="padding: 6px 10px; font-size: 0.75rem;" title="Visualizar Artigo">
                  <i data-lucide="external-link" size="14"></i>
                </a>
                <a href="<?= url('/admin/posts/edit/' . $p['id']) ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem;" title="Editar Artigo">
                  <i data-lucide="edit" size="14"></i> Editar
                </a>
                <button type="button" onclick="openConfirmModal({
                  title: 'Excluir Artigo do Blog?',
                  message: 'Deseja realmente excluir a publicação <strong><?= htmlspecialchars(addslashes($p['title'])) ?></strong>? O conteúdo e a imagem de capa serão removidos do site.',
                  actionUrl: '<?= url('/admin/posts/delete/' . $p['id']) ?>',
                  btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Excluir Artigo'
                })" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; border-color: #ef4444; color: #ef4444;" title="Excluir Artigo">
                  <i data-lucide="trash-2" size="14"></i>
                </button>
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
