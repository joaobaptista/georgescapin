<?php
$isEdit = !empty($procedure);
$pageTitle = $isEdit ? 'Editar Tratamento' : 'Novo Tratamento';
$currentIcon = $procedure['icon_name'] ?? 'sparkles';
$popularIcons = ['sparkles', 'droplet', 'scan-face', 'heart', 'shield', 'award', 'activity', 'eye', 'clock', 'star'];
ob_start();
?>

<div class="admin-card">
  <div style="margin-bottom: 25px;">
    <h3 style="color: var(--gold-light); font-size: 1.3rem;"><?= $isEdit ? 'Editar Procedimento' : 'Cadastrar Novo Procedimento' ?></h3>
    <p style="color: var(--text-muted); font-size: 0.85rem;">Preencha os dados do tratamento para exibição no site público.</p>
  </div>

  <form method="POST" action="<?= $isEdit ? url('/admin/procedures/update/' . $procedure['id']) : url('/admin/procedures/store') ?>" enctype="multipart/form-data" id="procForm">
    <?= csrf_field() ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="title">Título do Procedimento *</label>
        <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($procedure['title'] ?? '') ?>" placeholder="Ex: Toxina Botulínica">
      </div>

      <div class="form-group">
        <label for="slug">Slug da URL (Opcional)</label>
        <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($procedure['slug'] ?? '') ?>" placeholder="ex: toxina-botulinica">
      </div>
    </div>

    <div class="form-group">
      <label for="short_description">Descrição Curta (Exibida no Card) *</label>
      <textarea id="short_description" name="short_description" class="form-control" rows="3" required placeholder="Resumo em 1-2 frases para o card"><?= htmlspecialchars($procedure['short_description'] ?? '') ?></textarea>
    </div>

    <!-- EDITOR DE TEXTO RICO (QUILL WYSIWYG) -->
    <div class="form-group" style="margin-top: 20px;">
      <label style="font-weight: 600; font-size: 1rem; color: var(--gold-light); margin-bottom: 10px; display: block;">
        Descrição Completa / Detalhada (Editor Visual Rico)
      </label>
      
      <div id="quill-proc-editor">
        <?= $procedure['full_description'] ?? '' ?>
      </div>
      
      <textarea id="full_description" name="full_description" style="display: none;"><?= htmlspecialchars($procedure['full_description'] ?? '') ?></textarea>
      
      <small style="color: var(--text-muted); display: block; margin-top: 6px;">
        <i data-lucide="sparkles" size="14" style="vertical-align: middle;"></i> Formate textos, listas e insira fotos explicativas diretamente no conteúdo com conversão automática para WebP.
      </small>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 25px;">
      <div class="form-group">
        <label for="icon_name">Ícone Lucide</label>
        <input type="text" id="icon_name" name="icon_name" class="form-control" value="<?= htmlspecialchars($currentIcon) ?>" placeholder="ex: sparkles">
        <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
          <?php foreach ($popularIcons as $ic): ?>
            <button type="button" onclick="document.getElementById('icon_name').value = '<?= $ic ?>'" style="background: rgba(197, 160, 89, 0.1); border: 1px solid var(--card-border); color: var(--gold-primary); padding: 4px 8px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 0.75rem;">
              <i data-lucide="<?= $ic ?>" size="14"></i> <?= $ic ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label for="sort_order">Ordem de Exibição</label>
        <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= htmlspecialchars($procedure['sort_order'] ?? '0') ?>">
      </div>

      <div class="form-group" style="display: flex; flex-direction: column; justify-content: center;">
        <label style="margin-bottom: 10px;">Status de Visibilidade</label>
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main);">
          <input type="checkbox" name="is_active" value="1" <?= (!isset($procedure['is_active']) || $procedure['is_active'] == 1) ? 'checked' : '' ?> style="width: 20px; height: 20px;">
          Exibir no Site Público
        </label>
      </div>
    </div>

    <!-- BOX DE GESTÃO DA IMAGEM -->
    <div class="form-group" style="margin-top: 30px; background: rgba(197, 160, 89, 0.06); padding: 25px; border-radius: 10px; border: 1px solid var(--card-border);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <label style="font-weight: 600; font-size: 1.05rem; color: var(--gold-light); margin: 0; display: flex; align-items: center; gap: 8px;">
          <i data-lucide="image" size="20"></i> Imagem do Procedimento
        </label>

        <?php if (!empty($procedure['image_url'])): ?>
          <button type="button" onclick="document.getElementById('formRemoveProcImg').submit();" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
            <i data-lucide="trash-2" size="14"></i> Excluir Imagem Atual
          </button>
        <?php endif; ?>
      </div>
      
      <div style="display: flex; gap: 25px; align-items: flex-start; flex-wrap: wrap;">
        <!-- Imagem Atual Cadastrada -->
        <?php if (!empty($procedure['image_url'])): ?>
          <div id="currentProcImageWrapper" style="text-align: center; background: var(--bg-light); padding: 14px; border-radius: 8px; border: 1px solid var(--card-border); max-width: 220px;">
            <div style="font-size: 0.75rem; color: var(--gold-primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 4px;">
              <i data-lucide="check-circle" size="14"></i> Imagem Vinculada
            </div>
            <img src="<?= asset($procedure['image_url']) ?>" alt="Imagem Atual" style="width: 180px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); display: block; margin: 0 auto 8px;">
            <p style="font-size: 0.7rem; color: var(--text-muted); word-break: break-all; margin: 0;"><?= htmlspecialchars($procedure['image_url']) ?></p>
          </div>
        <?php else: ?>
          <div style="text-align: center; background: var(--bg-light); padding: 20px; border-radius: 8px; border: 1px dashed var(--card-border); width: 180px;">
            <i data-lucide="image-off" size="32" style="color: var(--text-muted); margin-bottom: 8px;"></i>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Nenhuma imagem anexada</p>
          </div>
        <?php endif; ?>

        <!-- Preview da Nova Imagem Selecionada -->
        <div id="newProcImagePreviewWrapper" style="display: none; text-align: center; background: var(--bg-light); padding: 14px; border-radius: 8px; border: 2px solid var(--gold-primary); max-width: 220px;">
          <div style="font-size: 0.75rem; color: #25D366; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 4px;">
            <i data-lucide="upload-cloud" size="14"></i> Nova Imagem Pronta
          </div>
          <img id="newProcImagePreview" src="" alt="Nova Imagem" style="width: 180px; height: 110px; object-fit: cover; border-radius: 6px; display: block; margin: 0 auto 8px;">
          <p id="newProcImageInfo" style="font-size: 0.7rem; color: var(--text-main); margin: 0; font-weight: 500;"></p>
        </div>

        <!-- Input de Upload -->
        <div style="flex: 1; min-width: 260px;">
          <label for="image_file" style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 8px; display: block;">
            <?= !empty($procedure['image_url']) ? 'Substituir por outra imagem do computador:' : 'Selecionar imagem do computador:' ?>
          </label>
          <input type="file" id="image_file" name="image_file" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml" onchange="previewProcUpload(this)">
          <input type="hidden" name="image_url" value="<?= htmlspecialchars($procedure['image_url'] ?? '') ?>">
          
          <div style="margin-top: 8px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
            Formatos aceitos: <strong>JPG, PNG, WebP, GIF, SVG</strong>.<br>
            A imagem será convertida automaticamente para <strong>WebP em alta definição</strong>.
          </div>
        </div>
      </div>
    </div>

    <div style="display: flex; gap: 15px; margin-top: 30px;">
      <button type="submit" class="btn-primary btn-solid" style="padding: 12px 35px;">
        <i data-lucide="save" size="18"></i> Salvar Tratamento
      </button>
      <a href="<?= url('/admin/procedures') ?>" class="btn-primary" style="padding: 12px 25px;">Cancelar</a>
    </div>
  </form>

  <?php if ($isEdit && !empty($procedure['image_url'])): ?>
    <!-- Formulário invisível para exclusão direta da imagem -->
    <form id="formRemoveProcImg" method="POST" action="<?= url('/admin/procedures/remove-image/' . $procedure['id']) ?>" onsubmit="return confirm('Deseja realmente remover a imagem deste procedimento?');" style="display: none;">
      <?= csrf_field() ?>
    </form>
  <?php endif; ?>
</div>

<script>
  // Inicialização do Quill Rich Text Editor para Procedimentos
  const procToolbarOptions = [
    [{ 'header': [2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'align': [] }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
  ];

  const quillProc = new Quill('#quill-proc-editor', {
    theme: 'snow',
    placeholder: 'Escreva e formate a descrição completa do tratamento...',
    modules: {
      toolbar: {
        container: procToolbarOptions,
        handlers: {
          image: procImageUploadHandler
        }
      }
    }
  });

  // Sincroniza o HTML do editor com o textarea antes do envio do formulário
  const procForm = document.getElementById('procForm');
  if (procForm) {
    procForm.addEventListener('submit', function() {
      document.getElementById('full_description').value = quillProc.root.innerHTML;
    });
  }

  // Upload assíncrono de imagens no editor de procedimentos (com conversão para WebP)
  function procImageUploadHandler() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/png, image/jpeg, image/webp, image/gif');
    input.click();

    input.onchange = async () => {
      const file = input.files[0];
      if (file) {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', '<?= csrf_token() ?>');

        try {
          const res = await fetch('<?= url('/admin/media/upload') ?>', {
            method: 'POST',
            body: formData
          });
          const data = await res.json();
          if (data.success && data.url) {
            const range = quillProc.getSelection(true);
            quillProc.insertEmbed(range.index, 'image', data.url);
            quillProc.setSelection(range.index + 1);
          } else {
            alert('Erro ao enviar imagem: ' + (data.error || 'Falha no processamento'));
          }
        } catch (err) {
          console.error(err);
          alert('Erro na comunicação com o servidor para upload da imagem.');
        }
      }
    };
  }

  // Preview da Imagem de Capa do Procedimento
  function previewProcUpload(input) {
    const wrapper = document.getElementById('newProcImagePreviewWrapper');
    const img = document.getElementById('newProcImagePreview');
    const info = document.getElementById('newProcImageInfo');
    
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const sizeKb = Math.round(file.size / 1024);
      info.textContent = file.name + ' (' + (sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB') + ')';

      const reader = new FileReader();
      reader.onload = function(e) {
        img.src = e.target.result;
        wrapper.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      wrapper.style.display = 'none';
    }
  }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
