<?php
$isEdit = !empty($page);
$pageTitle = $isEdit ? 'Editar Página Personalizada' : 'Criar Nova Página';
$activeTab = 'pages';
$backUrl = url('/admin/pages');
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('/admin')],
    ['label' => 'Páginas', 'url' => url('/admin/pages')],
    ['label' => $pageTitle]
];
ob_start();
?>

<div class="admin-card">
  <form method="POST" action="<?= $isEdit ? url('/admin/custom-pages/update/' . $page['id']) : url('/admin/custom-pages/store') ?>" enctype="multipart/form-data" id="customPageForm">
    <?= csrf_field() ?>

    <div class="admin-grid-2col">
      <div class="form-group">
        <label for="title">Título da Página *</label>
        <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($page['title'] ?? '') ?>" placeholder="Ex: Protocolo Rejuvenescimento 40+">
      </div>

      <div class="form-group">
        <label for="slug">Slug da URL (/p/seu-slug) *</label>
        <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" placeholder="ex: protocolo-40-mais">
      </div>
    </div>

    <div class="form-group">
      <label for="subtitle">Subtítulo / Chamada no Topo</label>
      <input type="text" id="subtitle" name="subtitle" class="form-control" value="<?= htmlspecialchars($page['subtitle'] ?? '') ?>" placeholder="Frase de efeito abaixo do título principal">
    </div>

    <!-- EDITOR DE TEXTO RICO (QUILL WYSIWYG) -->
    <div class="form-group" style="margin-top: 24px;">
      <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); margin-bottom: 8px; display: block;">
        Conteúdo da Página (Editor Visual Rico) *
      </label>
      
      <div id="quill-page-editor" style="min-height: 250px; background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 0 0 var(--radius-sm) var(--radius-sm);">
        <?= $page['content'] ?? '' ?>
      </div>
      
      <input type="hidden" id="content" name="content" value="<?= htmlspecialchars($page['content'] ?? '') ?>">
      
      <small style="color: var(--text-muted); display: block; margin-top: 6px;">
        <i data-lucide="sparkles" size="14" style="vertical-align: middle;"></i> Formate títulos, textos, links e insira fotos que são convertidas automaticamente para WebP.
      </small>
    </div>

    <!-- BANNER DE CABEÇALHO -->
    <div class="form-group" style="margin-top: 30px; background: var(--bg-canvas); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
      <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="image" size="18" style="color: var(--pastel-blue-accent);"></i> Imagem de Banner do Topo (Opcional)
      </label>

      <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
        <?php if (!empty($page['banner_image'])): ?>
          <div style="text-align: center; background: var(--bg-surface); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); max-width: 220px;">
            <img src="<?= asset($page['banner_image']) ?>" alt="Banner" style="width: 180px; height: 100px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); display: block; margin: 0 auto 8px;">
            <p style="font-size: 0.7rem; color: var(--text-muted); word-break: break-all; margin: 0;"><?= htmlspecialchars($page['banner_image']) ?></p>
          </div>
        <?php endif; ?>

        <div style="flex: 1; min-width: 260px;">
          <input type="file" id="banner_file" name="banner_file" class="form-control" accept="image/*">
          <input type="hidden" name="banner_image" value="<?= htmlspecialchars($page['banner_image'] ?? '') ?>">
          <small style="color: var(--text-muted); display: block; margin-top: 6px;">Conversão automática para WebP otimizado.</small>
        </div>
      </div>
    </div>

    <!-- SEO Meta Description -->
    <div class="form-group" style="margin-top: 20px;">
      <label for="meta_description">Meta Description (Resumo para Google e Compartilhamento)</label>
      <textarea id="meta_description" name="meta_description" class="form-control" rows="2" placeholder="Resumo de 1 a 2 frases"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
    </div>

    <div class="form-group" style="margin-top: 20px;">
      <?php if (!$isEdit): ?>
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); margin-bottom: 10px; font-weight: 500;">
          <input type="checkbox" name="add_to_menu" value="1" checked style="width: 18px; height: 18px; accent-color: var(--pastel-blue-accent);">
          <strong>Adicionar link desta página automaticamente no Menu de Navegação</strong>
        </label>
      <?php endif; ?>

      <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-weight: 500;">
        <input type="checkbox" name="is_published" value="1" <?= (!isset($page['is_published']) || $page['is_published'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--pastel-blue-accent);">
        Publicar Página no Site
      </label>
    </div>

    <div style="display: flex; gap: 12px; margin-top: 30px;">
      <button type="submit" class="btn-primary" style="padding: 10px 24px;">
        <i data-lucide="save" size="16"></i> Salvar Página
      </button>
      <a href="<?= url('/admin/pages') ?>" class="btn-secondary" style="padding: 10px 20px;">Cancelar</a>
    </div>
  </form>
</div>

<script>
  const pageToolbarOptions = [
    [{ 'header': [2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'align': [] }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
  ];

  const quillCustomPage = new Quill('#quill-page-editor', {
    theme: 'snow',
    placeholder: 'Escreva e formate todo o conteúdo da nova página...',
    modules: {
      toolbar: {
        container: pageToolbarOptions,
        handlers: {
          image: customPageImgHandler
        }
      }
    }
  });

  // Sincronização em tempo real
  quillCustomPage.on('text-change', function() {
    document.getElementById('content').value = quillCustomPage.root.innerHTML;
  });

  const pageForm = document.getElementById('customPageForm');
  if (pageForm) {
    pageForm.addEventListener('submit', function(e) {
      const html = quillCustomPage.root.innerHTML;
      const text = quillCustomPage.getText().trim();
      if (!text && !quillCustomPage.root.querySelector('img')) {
        alert('Por favor, preencha o conteúdo da página.');
        e.preventDefault();
        return false;
      }
      document.getElementById('content').value = html;
    });
  }

  function customPageImgHandler() {
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
            const range = quillCustomPage.getSelection(true);
            quillCustomPage.insertEmbed(range.index, 'image', data.url);
            quillCustomPage.setSelection(range.index + 1);
          } else {
            alert('Erro ao enviar imagem: ' + (data.error || 'Falha'));
          }
        } catch (err) {
          console.error(err);
          alert('Erro ao enviar imagem para o servidor.');
        }
      }
    };
  }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
