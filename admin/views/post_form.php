<?php
$isEdit = !empty($post);
$pageTitle = $isEdit ? 'Editar Artigo' : 'Novo Artigo do Blog';
$activeTab = 'posts';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('/admin')],
    ['label' => 'Artigos do Blog', 'url' => url('/admin/posts')],
    ['label' => $pageTitle]
];
$pageActions = '<a href="' . url('/admin/posts') . '" class="btn-admin btn-secondary"><i data-lucide="arrow-left" size="14"></i> Voltar aos Artigos</a>';
ob_start();
?>

<div class="admin-card">
  <form method="POST" action="<?= $isEdit ? url('/admin/posts/update/' . $post['id']) : url('/admin/posts/store') ?>" enctype="multipart/form-data" id="postForm">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="title">Título do Artigo *</label>
      <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($post['title'] ?? '') ?>" placeholder="Ex: Toxina Botulínica: O Segredo da Prevenção">
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="slug">Slug da URL (Opcional)</label>
        <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" placeholder="ex: toxina-botulinica-prevencao">
      </div>

      <div class="form-group">
        <label for="author">Autor</label>
        <input type="text" id="author" name="author" class="form-control" value="<?= htmlspecialchars($post['author'] ?? 'Dr. George') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="summary">Resumo / Chamada (Exibido na Listagem) *</label>
      <textarea id="summary" name="summary" class="form-control" rows="3" required placeholder="Breve resumo de 1-2 frases"><?= htmlspecialchars($post['summary'] ?? '') ?></textarea>
    </div>

    <!-- EDITOR DE TEXTO RICO (QUILL WYSIWYG) -->
    <div class="form-group" style="margin-top: 25px;">
      <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 8px; display: block;">
        Conteúdo Completo do Artigo (Editor Visual Rico) *
      </label>
      
      <div id="quill-editor">
        <?= $post['content'] ?? '' ?>
      </div>
      
      <input type="hidden" id="content" name="content" value="<?= htmlspecialchars($post['content'] ?? '') ?>">
      
      <p class="form-hint" style="margin-top: 6px;">
        <i data-lucide="sparkles" size="14" style="vertical-align: middle; color: var(--pastel-blue-accent);"></i> Suporta títulos, listas, formatação, vídeos e <strong>inserção direta de fotos otimizadas em WebP</strong>.
      </p>
    </div>

    <!-- BOX DE GESTÃO DA IMAGEM PRINCIPAL -->
    <div class="form-group" style="margin-top: 30px; background: var(--bg-surface-hover); padding: 22px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
        <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
          <i data-lucide="image" size="18" style="color: var(--pastel-blue-accent);"></i> Imagem de Capa Principal
        </label>

        <?php if (!empty($post['image_url'])): ?>
          <button type="button" onclick="document.getElementById('formRemovePostImg').submit();" class="btn-admin btn-sm" style="background: var(--pastel-red-bg); border-color: var(--pastel-red-border); color: var(--pastel-red-text);">
            <i data-lucide="trash-2" size="13"></i> Excluir Capa Atual
          </button>
        <?php endif; ?>
      </div>
      
      <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
        <!-- Imagem Atual Cadastrada -->
        <?php if (!empty($post['image_url'])): ?>
          <div id="currentPostImageWrapper" style="text-align: center; background: var(--bg-surface); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); max-width: 220px;">
            <div style="font-size: 0.75rem; color: var(--pastel-blue-text); font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; justify-content: center; gap: 4px;">
              <i data-lucide="check-circle" size="14"></i> Capa Vinculada
            </div>
            <img src="<?= asset($post['image_url']) ?>" alt="Capa Atual" style="width: 180px; height: 110px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-light); display: block; margin: 0 auto 8px;">
            <p style="font-size: 0.7rem; color: var(--text-muted); word-break: break-all; margin: 0;"><?= htmlspecialchars($post['image_url']) ?></p>
          </div>
        <?php else: ?>
          <div style="text-align: center; background: var(--bg-surface); padding: 18px; border-radius: var(--radius-sm); border: 1px dashed var(--border-light); width: 180px;">
            <i data-lucide="image-off" size="28" style="color: var(--text-muted); margin-bottom: 6px;"></i>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Nenhuma capa</p>
          </div>
        <?php endif; ?>

        <!-- Preview da Nova Imagem Selecionada -->
        <div id="newPostImagePreviewWrapper" style="display: none; text-align: center; background: var(--bg-surface); padding: 12px; border-radius: var(--radius-sm); border: 2px solid var(--pastel-green-accent); max-width: 220px;">
          <div style="font-size: 0.75rem; color: var(--pastel-green-text); font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; justify-content: center; gap: 4px;">
            <i data-lucide="upload-cloud" size="14"></i> Nova Capa Pronta
          </div>
          <img id="newPostImagePreview" src="" alt="Nova Capa" style="width: 180px; height: 110px; object-fit: cover; border-radius: var(--radius-sm); display: block; margin: 0 auto 8px;">
          <p id="newPostImageInfo" style="font-size: 0.7rem; color: var(--text-primary); margin: 0; font-weight: 500;"></p>
        </div>

        <!-- Input de Upload -->
        <div style="flex: 1; min-width: 260px;">
          <label for="image_file" style="font-size: 0.88rem; color: var(--text-primary); margin-bottom: 8px; display: block; font-weight: 500;">
            <?= !empty($post['image_url']) ? 'Substituir por outra imagem do computador:' : 'Selecionar imagem de capa do computador:' ?>
          </label>
          <input type="file" id="image_file" name="image_file" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml" onchange="previewPostUpload(this)">
          <input type="hidden" name="image_url" value="<?= htmlspecialchars($post['image_url'] ?? '') ?>">
          
          <p class="form-hint" style="margin-top: 8px; line-height: 1.5;">
            Formatos aceitos: <strong>JPG, PNG, WebP, GIF, SVG</strong>.<br>
            A imagem será convertida automaticamente para <strong>WebP em alta definição</strong>.
          </p>
        </div>
      </div>
    </div>

    <div class="form-group" style="margin-top: 20px;">
      <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary); font-size: 0.9rem;">
        <input type="checkbox" name="is_published" value="1" <?= (!isset($post['is_published']) || $post['is_published'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--pastel-blue-accent);">
        Publicar Artigo Imediatamente no Site
      </label>
    </div>

    <div style="display: flex; gap: 12px; margin-top: 30px;">
      <button type="submit" class="btn-admin btn-primary" style="padding: 12px 30px;">
        <i data-lucide="save" size="16"></i> Salvar Artigo
      </button>
      <a href="<?= url('/admin/posts') ?>" class="btn-admin btn-secondary" style="padding: 12px 20px;">Cancelar</a>
    </div>
  </form>

  <?php if ($isEdit && !empty($post['image_url'])): ?>
    <!-- Formulário invisível para exclusão direta da imagem -->
    <form id="formRemovePostImg" method="POST" action="<?= url('/admin/posts/remove-image/' . $post['id']) ?>" onsubmit="return confirm('Deseja realmente remover a imagem deste artigo?');" style="display: none;">
      <?= csrf_field() ?>
    </form>
  <?php endif; ?>
</div>

<script>
  // Inicialização do Quill Rich Text Editor
  const toolbarOptions = [
    [{ 'header': [2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'align': [] }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
  ];

  const quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Escreva e formate o conteúdo do artigo com imagens, títulos e parágrafos...',
    modules: {
      toolbar: {
        container: toolbarOptions,
        handlers: {
          image: imageUploadHandler
        }
      }
    }
  });

  quill.on('text-change', function() {
    document.getElementById('content').value = quill.root.innerHTML;
  });

  const postForm = document.getElementById('postForm');
  if (postForm) {
    postForm.addEventListener('submit', function(e) {
      const html = quill.root.innerHTML;
      const text = quill.getText().trim();
      if (!text && !quill.root.querySelector('img')) {
        alert('Por favor, escreva o conteúdo do artigo.');
        e.preventDefault();
        return false;
      }
      document.getElementById('content').value = html;
    });
  }

  function imageUploadHandler() {
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
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', data.url);
            quill.setSelection(range.index + 1);
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

  function previewPostUpload(input) {
    const wrapper = document.getElementById('newPostImagePreviewWrapper');
    const img = document.getElementById('newPostImagePreview');
    const info = document.getElementById('newPostImageInfo');
    
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
