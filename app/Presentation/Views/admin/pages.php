<?php
$pageTitle = 'Páginas do Site';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Todas as Páginas do Site</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Gerencie os conteúdos das páginas padrão e crie novas páginas institucionais com 1 clique.</p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="<?= url('/admin/custom-pages/create') ?>" class="btn-primary btn-solid" style="padding: 10px 20px;">
        <i data-lucide="plus" size="16"></i> Criar Nova Página
      </a>
      <a href="<?= url('/admin/menu') ?>" class="btn-primary" style="padding: 10px 18px;">
        <i data-lucide="menu" size="16"></i> Configurar Menu
      </a>
    </div>
  </div>

  <h4 style="font-size: 1.05rem; color: var(--gold-light); margin-bottom: 12px;">Páginas Principais do Sistema</h4>
  <table class="admin-table">
    <thead>
      <tr>
        <th style="width: 80px;">Prévia</th>
        <th>Página & URL</th>
        <th>Tipo</th>
        <th>Descrição do Conteúdo</th>
        <th>Status</th>
        <th style="text-align: right; width: 220px;">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pagesList as $page): ?>
        <?php $isInactive = ($page['status'] ?? '') === 'Inativa'; ?>
        <tr style="<?= $isInactive ? 'opacity: 0.65;' : '' ?>">
          <td>
            <div style="width: 70px; height: 48px; border-radius: 6px; overflow: hidden; border: 1px solid var(--card-border); background: var(--bg-light); display: flex; align-items: center; justify-content: center;">
              <?php if (!empty($page['image'])): ?>
                <img src="<?= asset($page['image']) ?>" alt="<?= htmlspecialchars($page['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <i data-lucide="layout" size="20" style="color: var(--gold-primary);"></i>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong style="font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($page['name']) ?></strong><br>
            <a href="<?= $page['url'] ?>" target="_blank" style="color: var(--gold-primary); font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
              <?= htmlspecialchars($page['slug']) ?> <i data-lucide="external-link" size="12"></i>
            </a>
          </td>
          <td>
            <span class="badge" style="background: rgba(197, 160, 89, 0.15); color: var(--gold-primary); border: 1px solid var(--card-border);">
              <?= htmlspecialchars($page['badge']) ?>
            </span>
          </td>
          <td style="color: var(--text-muted); font-size: 0.85rem; max-width: 300px; line-height: 1.4;">
            <?= htmlspecialchars($page['description']) ?>
          </td>
          <td>
            <span class="badge <?= $isInactive ? 'badge-muted' : 'badge-success' ?>">
              <?= htmlspecialchars($page['status']) ?>
            </span>
          </td>
          <td style="text-align: right;">
            <div style="display: inline-flex; gap: 6px; align-items: center;">
              <a href="<?= $page['url'] ?>" target="_blank" class="btn-primary" style="padding: 6px 10px; font-size: 0.8rem;" title="Ver Página ao Vivo">
                <i data-lucide="eye" size="14"></i>
              </a>
              <a href="<?= $page['edit_url'] ?>" class="btn-primary btn-solid" style="padding: 6px 12px; font-size: 0.8rem;" title="Editar Conteúdo">
                <i data-lucide="edit" size="14"></i> Editar
              </a>
              <?php if ($isInactive): ?>
                <button type="button" onclick="openConfirmModal({
                  title: 'Reativar Página?',
                  message: 'Deseja reativar a página <strong><?= htmlspecialchars(addslashes($page['name'])) ?></strong> (<?= htmlspecialchars($page['slug']) ?>) para que volte a ser publicada normalmente?',
                  actionUrl: '<?= $page['toggle_url'] ?>',
                  btnText: '<i data-lucide=\'check-circle\' size=\'14\'></i> Sim, Reativar',
                  type: 'primary'
                })" class="btn-primary" style="padding: 6px 10px; font-size: 0.8rem; border-color: #25D366; color: #25D366;" title="Reativar Página">
                  <i data-lucide="check-circle" size="14"></i>
                </button>
              <?php else: ?>
                <button type="button" onclick="openConfirmModal({
                  title: 'Excluir / Desativar Página?',
                  message: 'Tem certeza que deseja desativar/ocultar a página <strong><?= htmlspecialchars(addslashes($page['name'])) ?></strong> (<?= htmlspecialchars($page['slug']) ?>)? Ela ficará marcada como inativa.',
                  actionUrl: '<?= $page['delete_url'] ?>',
                  btnText: '<i data-lucide=\'trash-2\' size=\'14\'></i> Sim, Excluir',
                  type: 'danger'
                })" class="btn-primary" style="padding: 6px 10px; font-size: 0.8rem; border-color: #ef4444; color: #ef4444;" title="Excluir / Desativar Página">
                  <i data-lucide="trash-2" size="14"></i>
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- SEÇÃO DE PÁGINAS PERSONALIZADAS CRIADAS -->
  <?php if (!empty($customPages)): ?>
    <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid var(--card-border);">
      <h4 style="font-size: 1.05rem; color: var(--gold-light); margin-bottom: 12px;">Páginas Personalizadas Criadas por Você</h4>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Título da Página</th>
            <th>URL Pública</th>
            <th>Data de Criação</th>
            <th>Status</th>
            <th style="text-align: right; width: 220px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customPages as $cp): ?>
            <tr>
              <td>
                <strong style="color: var(--text-main);"><?= htmlspecialchars($cp['title']) ?></strong>
                <?php if (!empty($cp['subtitle'])): ?>
                  <small style="display: block; color: var(--text-muted); font-size: 0.75rem;"><?= htmlspecialchars($cp['subtitle']) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= url('/p/' . $cp['slug']) ?>" target="_blank" style="color: var(--gold-primary); font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                  /p/<?= htmlspecialchars($cp['slug']) ?> <i data-lucide="external-link" size="12"></i>
                </a>
              </td>
              <td style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d/m/Y', strtotime($cp['created_at'])) ?></td>
              <td>
                <span class="badge <?= $cp['is_published'] ? 'badge-success' : 'badge-muted' ?>">
                  <?= $cp['is_published'] ? 'Publicada' : 'Rascunho' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <a href="<?= url('/p/' . $cp['slug']) ?>" target="_blank" class="btn-primary" style="padding: 6px 10px; font-size: 0.75rem;" title="Ver Página">
                    <i data-lucide="eye" size="14"></i>
                  </a>
                  <a href="<?= url('/admin/custom-pages/edit/' . $cp['id']) ?>" class="btn-primary btn-solid" style="padding: 6px 12px; font-size: 0.75rem;" title="Editar Conteúdo">
                    <i data-lucide="edit" size="14"></i> Editar
                  </a>
                  <button type="button" onclick="openConfirmModal({
                    title: 'Excluir Página Customizada?',
                    message: 'Tem certeza que deseja excluir permanentemente a página <strong><?= htmlspecialchars(addslashes($cp['title'])) ?></strong> (/p/<?= htmlspecialchars($cp['slug']) ?>)? Esta ação não poderá ser desfeita.',
                    actionUrl: '<?= url('/admin/custom-pages/delete/' . $cp['id']) ?>',
                    btnText: '<i data-lucide=\'trash-2\' size=\'14\'></i> Sim, Excluir Página',
                    type: 'danger'
                  })" class="btn-primary" style="padding: 6px 10px; font-size: 0.75rem; border-color: #ef4444; color: #ef4444;" title="Excluir Página">
                    <i data-lucide="trash-2" size="14"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
