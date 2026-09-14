<?php
$pageTitle = 'Configurar Menu de Navegação';
ob_start();
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
  
  <!-- LISTAGEM E EDIÇÃO DOS ITENS DE MENU -->
  <div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 style="color: var(--gold-light); font-size: 1.3rem;">Estrutura do Menu do Site</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Estes links aparecem no cabeçalho e no menu mobile do site público.</p>
      </div>
      <a href="<?= url('/') ?>" target="_blank" class="btn-primary" style="padding: 8px 16px;">
        <i data-lucide="external-link" size="14"></i> Visualizar no Site
      </a>
    </div>

    <?php if (empty($menuItems)): ?>
      <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhum item configurado no menu ainda.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 70px;">Ordem</th>
            <th>Nome do Link</th>
            <th>Destino (URL)</th>
            <th>Estilo</th>
            <th>Status</th>
            <th style="text-align: right; width: 140px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menuItems as $item): ?>
            <tr>
              <td>
                <strong>#<?= $item['sort_order'] ?></strong>
              </td>
              <td>
                <strong style="color: var(--text-main); font-size: 0.95rem;"><?= htmlspecialchars($item['label']) ?></strong>
                <?php if ($item['target'] === '_blank'): ?>
                  <span style="font-size: 0.7rem; color: var(--text-muted); display: block;">(Abre em nova aba)</span>
                <?php endif; ?>
              </td>
              <td>
                <code style="background: var(--bg-light); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: var(--gold-primary);"><?= htmlspecialchars($item['url']) ?></code>
              </td>
              <td>
                <?php if (!empty($item['is_button'])): ?>
                  <span class="badge" style="background: rgba(197, 160, 89, 0.2); color: var(--gold-primary); border: 1px solid var(--gold-primary);">Botão Dourado</span>
                <?php else: ?>
                  <span style="color: var(--text-muted); font-size: 0.8rem;">Link Normal</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $item['is_active'] ? 'badge-success' : 'badge-muted' ?>">
                  <?= $item['is_active'] ? 'Visível' : 'Oculto' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 8px;">
                  <button type="button" onclick="editMenuItem(<?= htmlspecialchars(json_encode($item)) ?>)" class="btn-primary" style="padding: 6px 10px; font-size: 0.75rem;" title="Editar Link">
                    <i data-lucide="edit" size="14"></i>
                  </button>
                  <button type="button" onclick="openConfirmModal({
                    title: 'Remover Link do Menu?',
                    message: 'Deseja realmente remover o link <strong><?= htmlspecialchars(addslashes($item['label'])) ?></strong> da barra de navegação?',
                    actionUrl: '<?= url('/admin/menu/delete/' . $item['id']) ?>',
                    btnText: '<i data-lucide=\'trash-2\' size=\'15\'></i> Sim, Remover do Menu'
                  })" class="btn-primary" style="padding: 6px 10px; font-size: 0.75rem; border-color: #ef4444; color: #ef4444;" title="Excluir Link">
                    <i data-lucide="trash-2" size="14"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- FORMULÁRIO ADICIONAR / EDITAR ITEM -->
  <div class="admin-card">
    <h3 id="formMenuTitle" style="color: var(--gold-light); font-size: 1.2rem; margin-bottom: 8px;">+ Adicionar Link ao Menu</h3>
    <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Crie links personalizados ou vincule a páginas existentes.</p>

    <form id="formMenu" method="POST" action="<?= url('/admin/menu/store') ?>">
      <?= csrf_field() ?>

      <!-- Seletor Rápido de Páginas -->
      <div class="form-group">
        <label for="pageQuickSelect" style="font-size: 0.85rem;">Vincular a uma Página Existente (Opcional):</label>
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
            <optgroup label="Páginas Customizadas Criadas">
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

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-size: 0.9rem; margin-bottom: 8px;">
          <input type="checkbox" id="menu_is_button" name="is_button" value="1" style="width: 18px; height: 18px;">
          Destacar como Botão Dourado (CTA)
        </label>

        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-size: 0.9rem;">
          <input type="checkbox" id="menu_is_active" name="is_active" value="1" checked style="width: 18px; height: 18px;">
          Visível no Menu
        </label>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 25px;">
        <button type="submit" id="btnMenuSubmit" class="btn-primary btn-solid" style="flex: 1; justify-content: center; padding: 10px;">
          <i data-lucide="plus" size="16"></i> Adicionar ao Menu
        </button>
        <button type="button" id="btnCancelEdit" onclick="resetMenuForm()" class="btn-primary" style="display: none; padding: 10px 15px;">
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

    document.getElementById('btnMenuSubmit').innerHTML = '<i data-lucide="save" size="16"></i> Salvar Alterações';
    document.getElementById('btnCancelEdit').style.display = 'inline-flex';
    lucide.createIcons();

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function resetMenuForm() {
    document.getElementById('formMenuTitle').textContent = '+ Adicionar Link ao Menu';
    document.getElementById('formMenu').action = '<?= url('/admin/menu/store') ?>';
    document.getElementById('formMenu').reset();
    document.getElementById('menu_is_active').checked = true;
    document.getElementById('btnMenuSubmit').innerHTML = '<i data-lucide="plus" size="16"></i> Adicionar ao Menu';
    document.getElementById('btnCancelEdit').style.display = 'none';
    lucide.createIcons();
  }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
