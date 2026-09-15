<?php
$pageTitle = 'Menu de Navegação';
$activeTab = 'menu';
ob_start();
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start;">
  
  <!-- LISTAGEM E EDIÇÃO DOS ITENS DE MENU -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">Estrutura de Links do Menu</h3>
    </div>

    <div class="admin-table-container">
      <?php if (empty($menuItems)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 35px;">Nenhum item configurado no menu ainda.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 70px;">Ordem</th>
              <th>Nome do Link</th>
              <th>Destino (URL)</th>
              <th>Estilo</th>
              <th>Status</th>
              <th style="text-align: right; width: 80px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($menuItems as $item): ?>
              <tr>
                <td style="color: var(--text-muted); font-weight: 600;">#<?= $item['sort_order'] ?></td>
                <td>
                  <strong style="color: var(--text-primary); font-size: 0.92rem;"><?= htmlspecialchars($item['label']) ?></strong>
                  <?php if ($item['target'] === '_blank'): ?>
                    <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">(Abre em nova aba)</span>
                  <?php endif; ?>
                </td>
                <td>
                  <code style="background: var(--bg-surface-hover); padding: 3px 8px; border-radius: var(--radius-xs); font-size: 0.8rem; color: var(--pastel-blue-text); border: 1px solid var(--border-light);"><?= htmlspecialchars($item['url']) ?></code>
                </td>
                <td>
                  <?php if (!empty($item['is_button'])): ?>
                    <span class="badge badge-info">Botão CTA</span>
                  <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.82rem;">Link Normal</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $item['is_active'] ? 'badge-success' : 'badge-muted' ?>">
                    <?= $item['is_active'] ? 'Visível' : 'Oculto' ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <div class="table-action-dropdown">
                    <button type="button" class="table-action-trigger" title="Mais Ações" aria-label="Mais Ações">
                      <i data-lucide="more-horizontal" size="18"></i>
                    </button>
                    <div class="table-action-menu">
                      <button type="button" onclick="editMenuItem(<?= htmlspecialchars(json_encode($item)) ?>)" class="table-action-item">
                        <i data-lucide="edit-3" size="15"></i> Editar Link
                      </button>
                      <div class="table-action-divider"></div>
                      <button type="button" onclick="openConfirmModal({
                        title: 'Remover Link do Menu?',
                        message: 'Deseja realmente remover o link <strong><?= htmlspecialchars(addslashes($item['label'])) ?></strong> da barra de navegação?',
                        actionUrl: '<?= url('/admin/menu/delete/' . $item['id']) ?>',
                        btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Remover',
                        type: 'danger'
                      })" class="table-action-item danger">
                        <i data-lucide="trash-2" size="15"></i> Excluir Link
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- FORMULÁRIO ADICIONAR / EDITAR ITEM -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 id="formMenuTitle" class="admin-card-title">+ Adicionar Link</h3>
    </div>
    <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 18px;">Crie links personalizados ou vincule a páginas existentes.</p>

    <form id="formMenu" method="POST" action="<?= url('/admin/menu/store') ?>">
      <?= csrf_field() ?>

      <!-- Seletor Rápido de Páginas -->
      <div class="form-group">
        <label for="pageQuickSelect" style="font-size: 0.85rem;">Vincular Página Existente:</label>
        <select id="pageQuickSelect" class="form-control" onchange="applyPageQuickSelect(this)" style="font-size: 0.85rem;">
          <option value="">-- Selecionar Página Rápida --</option>
          <optgroup label="Páginas Padrão">
            <option value="/" data-label="Início">Início (/)</option>
            <option value="/clinica" data-label="George Scapin">George Scapin (/clinica)</option>
            <option value="/procedimentos" data-label="Procedimentos">Procedimentos (/procedimentos)</option>
            <option value="/harmonizacao-facial" data-label="Harmonização Facial">Harmonização Facial (/harmonizacao-facial)</option>
            <option value="/blog" data-label="Blog">Blog (/blog)</option>
            <option value="/contato" data-label="Contato">Contato (/contato)</option>
          </optgroup>
          <?php if (!empty($customPages)): ?>
            <optgroup label="Páginas Customizadas">
              <?php foreach ($customPages as $cp): ?>
                <option value="/p/<?= htmlspecialchars($cp['slug']) ?>" data-label="<?= htmlspecialchars($cp['title']) ?>">
                  <?= htmlspecialchars($cp['title']) ?> (/p/<?= htmlspecialchars($cp['slug']) ?>)
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="menu_label">Nome do Link no Menu *</label>
        <input type="text" id="menu_label" name="label" class="form-control" required placeholder="Ex: Avaliação VIP, Sobre Nós">
      </div>

      <div class="form-group">
        <label for="menu_url">URL de Destino *</label>
        <input type="text" id="menu_url" name="url" class="form-control" required placeholder="Ex: /minha-pagina ou https://...">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div class="form-group">
          <label for="menu_order">Ordem</label>
          <input type="number" id="menu_order" name="sort_order" class="form-control" value="10">
        </div>

        <div class="form-group">
          <label for="menu_target">Destino</label>
          <select id="menu_target" name="target" class="form-control">
            <option value="_self">Mesma Aba (_self)</option>
            <option value="_blank">Nova Aba (_blank)</option>
          </select>
        </div>
      </div>

      <div class="form-group" style="margin-top: 10px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-primary); font-size: 0.88rem; margin-bottom: 8px;">
          <input type="checkbox" id="menu_is_button" name="is_button" value="1" style="width: 16px; height: 16px; accent-color: var(--pastel-blue-accent);">
          Destacar como Botão CTA
        </label>

        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-primary); font-size: 0.88rem;">
          <input type="checkbox" id="menu_is_active" name="is_active" value="1" checked style="width: 16px; height: 16px; accent-color: var(--pastel-blue-accent);">
          Visível no Menu
        </label>
      </div>

      <div style="display: flex; gap: 8px; margin-top: 22px;">
        <button type="submit" id="btnMenuSubmit" class="btn-admin btn-primary" style="flex: 1; justify-content: center;">
          <i data-lucide="plus" size="15"></i> Adicionar ao Menu
        </button>
        <button type="button" id="btnCancelEdit" onclick="resetMenuForm()" class="btn-admin btn-secondary" style="display: none;">
          Cancelar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function applyPageQuickSelect(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.value) {
      document.getElementById('menu_url').value = selectedOption.value;
      if (!document.getElementById('menu_label').value) {
        document.getElementById('menu_label').value = selectedOption.getAttribute('data-label') || '';
      }
    }
  }

  function editMenuItem(item) {
    document.getElementById('formMenuTitle').textContent = '✏️ Editar Link: ' + item.label;
    document.getElementById('formMenu').action = '<?= url('/admin/menu/update/') ?>' + item.id;
    document.getElementById('menu_label').value = item.label;
    document.getElementById('menu_url').value = item.url;
    document.getElementById('menu_order').value = item.sort_order;
    document.getElementById('menu_target').value = item.target || '_self';
    document.getElementById('menu_is_button').checked = (item.is_button == 1);
    document.getElementById('menu_is_active').checked = (item.is_active == 1);

    document.getElementById('btnMenuSubmit').innerHTML = '<i data-lucide="save" size="15"></i> Salvar Alterações';
    document.getElementById('btnCancelEdit').style.display = 'inline-flex';
    lucide.createIcons();

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function resetMenuForm() {
    document.getElementById('formMenuTitle').textContent = '+ Adicionar Link';
    document.getElementById('formMenu').action = '<?= url('/admin/menu/store') ?>';
    document.getElementById('formMenu').reset();
    document.getElementById('menu_is_active').checked = true;
    document.getElementById('btnMenuSubmit').innerHTML = '<i data-lucide="plus" size="15"></i> Adicionar ao Menu';
    document.getElementById('btnCancelEdit').style.display = 'none';
    lucide.createIcons();
  }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
