<?php
$pageTitle = 'Editar Página: George Scapin / Clínica';
$activeTab = 'pages';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('/admin')],
    ['label' => 'Páginas do Site', 'url' => url('/admin/pages')],
    ['label' => 'A Clínica']
];
$pageActions = '<a href="' . url('/admin/pages') . '" class="btn-admin btn-secondary"><i data-lucide="arrow-left" size="14"></i> Voltar</a>';
ob_start();
?>

<form method="POST" action="<?= url('/admin/pages/clinic') ?>" enctype="multipart/form-data" id="clinicPageForm">
  <?= csrf_field() ?>

  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">1. Informações Principais & Cabeçalho</h3>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="clinic_title">Título da Página (Nome do Profissional) *</label>
        <input type="text" id="clinic_title" name="clinic_title" class="form-control" required value="<?= htmlspecialchars($clinic['title'] ?? 'Dr. George Scapin') ?>">
      </div>

      <div class="form-group">
        <label for="clinic_subtitle">Subtítulo / Especialidades e CRBM *</label>
        <input type="text" id="clinic_subtitle" name="clinic_subtitle" class="form-control" required value="<?= htmlspecialchars($clinic['subtitle'] ?? 'Biomédico Esteta | CRBM 5202<br>Especialista em Harmonização Facial Avançada') ?>">
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
      <div class="form-group">
        <label for="clinic_section_title">Título do Bloco Lateral (Frase Principal)</label>
        <input type="text" id="clinic_section_title" name="clinic_section_title" class="form-control" value="<?= htmlspecialchars($settings['clinic_section_title'] ?? 'Ciência, Precisão e<br>Sensibilidade Artística.') ?>">
        <p class="form-hint">Dica: Pode usar &lt;br&gt; para quebrar linha.</p>
      </div>

      <div class="form-group">
        <label for="clinic_cta_btn">Texto do Botão de Agendamento</label>
        <input type="text" id="clinic_cta_btn" name="clinic_cta_btn" class="form-control" value="<?= htmlspecialchars($settings['clinic_cta_btn'] ?? 'Agendar Consulta com Dr. George') ?>">
      </div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title">2. Biografia & Filosofia de Atendimento</h3>
    </div>

    <!-- BIOGRAFIA PARÁGRAFO 1 (TEXTO RICO COM QUILL) -->
    <div class="form-group">
      <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 8px; display: block;">
        Primeiro Bloco da Biografia (Apresentação e Metodologia) *
      </label>
      <div id="quill-clinic-p1" style="min-height: 180px;">
        <?= $clinic['paragraph_1'] ?? '' ?>
      </div>
      <textarea id="clinic_p1" name="clinic_p1" style="display: none;"><?= htmlspecialchars($clinic['paragraph_1'] ?? '') ?></textarea>
    </div>

    <!-- BIOGRAFIA PARÁGRAFO 2 (TEXTO RICO COM QUILL) -->
    <div class="form-group" style="margin-top: 25px;">
      <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 8px; display: block;">
        Segundo Bloco da Biografia (Filosofia e Resultados) *
      </label>
      <div id="quill-clinic-p2" style="min-height: 180px;">
        <?= $clinic['paragraph_2'] ?? '' ?>
      </div>
      <textarea id="clinic_p2" name="clinic_p2" style="display: none;"><?= htmlspecialchars($clinic['paragraph_2'] ?? '') ?></textarea>
    </div>

    <div class="form-group" style="margin-top: 25px;">
      <label for="clinic_quote">Citação em Destaque (Frase de Efeito)</label>
      <input type="text" id="clinic_quote" name="clinic_quote" class="form-control" value="<?= htmlspecialchars($clinic['highlight_quote'] ?? 'A verdadeira elegância está na naturalidade.') ?>" placeholder="Ex: A verdadeira elegância está na naturalidade.">
    </div>
  </div>

  <!-- BOX DE GESTÃO DA FOTO OFICIAL -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="camera" size="18" style="color: var(--pastel-blue-accent);"></i> 3. Foto Oficial do Dr. George Scapin
      </h3>
    </div>

    <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
      <!-- Imagem Atual -->
      <?php if (!empty($clinic['image_url'])): ?>
        <div style="text-align: center; background: var(--bg-surface-hover); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); max-width: 220px;">
          <div style="font-size: 0.75rem; color: var(--pastel-blue-text); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 4px;">
            <i data-lucide="check-circle" size="14"></i> Foto Atual
          </div>
          <img src="<?= asset($clinic['image_url']) ?>" alt="Dr. George Scapin" style="width: 180px; height: 200px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-light); display: block; margin: 0 auto 8px;">
          <p style="font-size: 0.72rem; color: var(--text-muted); word-break: break-all; margin: 0;"><?= htmlspecialchars($clinic['image_url']) ?></p>
        </div>
      <?php endif; ?>

      <!-- Preview da Nova Foto -->
      <div id="newClinicImgPreviewWrapper" style="display: none; text-align: center; background: var(--bg-surface-hover); padding: 14px; border-radius: var(--radius-sm); border: 2px solid var(--pastel-green-accent); max-width: 220px;">
        <div style="font-size: 0.75rem; color: var(--pastel-green-text); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 4px;">
          <i data-lucide="upload-cloud" size="14"></i> Nova Foto Pronta
        </div>
        <img id="newClinicImgPreview" src="" alt="Nova Foto" style="width: 180px; height: 200px; object-fit: cover; border-radius: var(--radius-sm); display: block; margin: 0 auto 8px;">
        <p id="newClinicImgInfo" style="font-size: 0.72rem; color: var(--text-primary); margin: 0; font-weight: 500;"></p>
      </div>

      <!-- Input de Upload -->
      <div style="flex: 1; min-width: 260px;">
        <label for="image_file" style="font-size: 0.9rem; color: var(--text-primary); margin-bottom: 8px; display: block; font-weight: 500;">
          Substituir por outra foto do computador:
        </label>
        <input type="file" id="image_file" name="image_file" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif" onchange="previewClinicUpload(this)">
        
        <p class="form-hint" style="margin-top: 8px; line-height: 1.5;">
          Formatos aceitos: <strong>JPG, PNG, WebP, GIF</strong>.<br>
          A foto será otimizada e convertida automaticamente para <strong>WebP em alta definição</strong>.
        </p>
      </div>
    </div>
  </div>

  <div style="display: flex; gap: 12px; margin-top: 25px; margin-bottom: 50px;">
    <button type="submit" class="btn-admin btn-primary" style="padding: 12px 30px;">
      <i data-lucide="save" size="16"></i> Salvar Conteúdo da Página
    </button>
    <a href="<?= url('/admin/pages') ?>" class="btn-admin btn-secondary" style="padding: 12px 20px;">Cancelar</a>
  </div>
</form>

<script>
  // Quill toolbar para os blocos da biografia
  const clinicToolbar = [
    ['bold', 'italic', 'underline'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    ['link'],
    ['clean']
  ];

  const quillP1 = new Quill('#quill-clinic-p1', {
    theme: 'snow',
    placeholder: 'Escreva a introdução biográfica...',
    modules: { toolbar: clinicToolbar }
  });

  const quillP2 = new Quill('#quill-clinic-p2', {
    theme: 'snow',
    placeholder: 'Escreva sobre a filosofia de atendimento e excelência...',
    modules: { toolbar: clinicToolbar }
  });

  const clinicForm = document.getElementById('clinicPageForm');
  if (clinicForm) {
    clinicForm.addEventListener('submit', function() {
      document.getElementById('clinic_p1').value = quillP1.root.innerHTML;
      document.getElementById('clinic_p2').value = quillP2.root.innerHTML;
    });
  }

  function previewClinicUpload(input) {
    const wrapper = document.getElementById('newClinicImgPreviewWrapper');
    const img = document.getElementById('newClinicImgPreview');
    const info = document.getElementById('newClinicImgInfo');
    
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
