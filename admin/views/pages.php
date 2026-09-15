<?php
$pageTitle = 'Páginas do Site';
$activeTab = 'pages';
$pageActions = '<a href="' . url('/admin/custom-pages/create') . '" class="btn-admin btn-primary"><i data-lucide="plus" size="16"></i> Nova Página</a>';
ob_start();
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Páginas Principais do Sistema</h3>
  </div>

  <div class="admin-table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 80px;">Prévia</th>
          <th>Página & URL</th>
          <th>Tipo</th>
          <th>Descrição do Conteúdo</th>
          <th>Status</th>
          <th style="text-align: right; width: 140px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pagesList as $page): ?>
          <?php $isInactive = ($page['status'] ?? '') === 'Inativa'; ?>
          <tr style="<?= $isInactive ? 'opacity: 0.65;' : '' ?>">
            <td>
              <div style="width: 60px; height: 40px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border-light); background: var(--bg-surface-hover); display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($page['image'])): ?>
                  <img src="<?= asset($page['image']) ?>" alt="<?= htmlspecialchars($page['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <i data-lucide="layout" size="18" style="color: var(--pastel-blue-accent);"></i>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <strong style="font-size: 0.92rem; color: var(--text-primary);"><?= htmlspecialchars($page['name']) ?></strong><br>
              <a href="<?= $page['url'] ?>" target="_blank" style="color: var(--pastel-blue-text); font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                <?= htmlspecialchars($page['slug']) ?> <i data-lucide="external-link" size="12"></i>
              </a>
            </td>
            <td>
              <span class="badge badge-info">
                <?= htmlspecialchars($page['badge']) ?>
              </span>
            </td>
            <td style="color: var(--text-secondary); font-size: 0.85rem; max-width: 280px; line-height: 1.4;">
              <?= htmlspecialchars($page['description']) ?>
            </td>
            <td>
              <span class="badge <?= $isInactive ? 'badge-muted' : 'badge-success' ?>">
                <?= htmlspecialchars($page['status']) ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="table-action-dropdown">
                <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                  <i data-lucide="more-horizontal" size="18"></i>
                </button>
                <div class="table-action-menu">
                  <a href="<?= $page['url'] ?>" target="_blank" class="table-action-item">
                    <i data-lucide="eye" size="15"></i> Ver no Site
                  </a>
                  <a href="<?= $page['edit_url'] ?>" class="table-action-item">
                    <i data-lucide="edit-3" size="15"></i> Editar Conteúdo
                  </a>
                  <div class="table-action-divider"></div>
                  <?php if ($isInactive): ?>
                    <button type="button" onclick="openConfirmModal({
                      title: 'Reativar Página?',
                      message: 'Deseja reativar a página <strong><?= htmlspecialchars(addslashes($page['name'])) ?></strong> (<?= htmlspecialchars($page['slug']) ?>) para que volte a ser publicada normalmente?',
                      actionUrl: '<?= $page['toggle_url'] ?>',
                      btnText: '<i data-lucide=\'check-circle\' size=\'14\'></i> Reativar',
                      type: 'primary'
                    })" class="table-action-item" style="color: var(--pastel-green-accent);">
                      <i data-lucide="check-circle" size="15"></i> Reativar Página
                    </button>
                  <?php else: ?>
                    <button type="button" onclick="openConfirmModal({
                      title: 'Excluir / Desativar Página?',
                      message: 'Tem certeza que deseja desativar/ocultar a página <strong><?= htmlspecialchars(addslashes($page['name'])) ?></strong> (<?= htmlspecialchars($page['slug']) ?>)? Ela ficará marcada como inativa.',
                      actionUrl: '<?= $page['delete_url'] ?>',
                      btnText: '<i data-lucide=\'trash-2\' size=\'14\'></i> Desativar',
                      type: 'danger'
                    })" class="table-action-item danger">
                      <i data-lucide="trash-2" size="15"></i> Desativar Página
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SEÇÃO DE PÁGINAS PERSONALIZADAS CRIADAS -->
<?php if (!empty($customPages)): ?>
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Páginas Personalizadas Criadas</h3>
    </div>
    
    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Título da Página</th>
            <th>URL Pública</th>
            <th>Data de Criação</th>
            <th>Status</th>
            <th style="text-align: right; width: 80px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customPages as $cp): ?>
            <tr>
              <td>
                <strong style="color: var(--text-primary);"><?= htmlspecialchars($cp['title']) ?></strong>
                <?php if (!empty($cp['subtitle'])): ?>
                  <small style="display: block; color: var(--text-muted); font-size: 0.78rem;"><?= htmlspecialchars($cp['subtitle']) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= url('/p/' . $cp['slug']) ?>" target="_blank" style="color: var(--pastel-blue-text); font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                  /p/<?= htmlspecialchars($cp['slug']) ?> <i data-lucide="external-link" size="12"></i>
                </a>
              </td>
              <td style="color: var(--text-muted); font-size: 0.82rem;"><?= date('d/m/Y', strtotime($cp['created_at'])) ?></td>
              <td>
                <span class="badge <?= $cp['is_published'] ? 'badge-success' : 'badge-draft' ?>">
                  <?= $cp['is_published'] ? 'Publicada' : 'Rascunho' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div class="table-action-dropdown">
                  <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                    <i data-lucide="more-horizontal" size="18"></i>
                  </button>
                  <div class="table-action-menu">
                    <a href="<?= url('/p/' . $cp['slug']) ?>" target="_blank" class="table-action-item">
                      <i data-lucide="eye" size="15"></i> Ver Página
                    </a>
                    <a href="<?= url('/admin/custom-pages/edit/' . $cp['id']) ?>" class="table-action-item">
                      <i data-lucide="edit-3" size="15"></i> Editar Página
                    </a>
                    <div class="table-action-divider"></div>
                    <button type="button" onclick="openConfirmModal({
                      title: 'Excluir Página Customizada?',
                      message: 'Tem certeza que deseja excluir permanentemente a página <strong><?= htmlspecialchars(addslashes($cp['title'])) ?></strong> (/p/<?= htmlspecialchars($cp['slug']) ?>)? Esta ação não poderá ser desfeita.',
                      actionUrl: '<?= url('/admin/custom-pages/delete/' . $cp['id']) ?>',
                      btnText: '<i data-lucide=\'trash-2\' size=\'14\'></i> Sim, Excluir',
                      type: 'danger'
                    })" class="table-action-item danger">
                      <i data-lucide="trash-2" size="15"></i> Excluir Página
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
