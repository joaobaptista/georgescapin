<?php
$pageTitle = 'Editar Página: Harmonização Facial';
$activeTab = 'pages';
$backUrl = url('/admin/pages');
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('/admin')],
    ['label' => 'Páginas', 'url' => url('/admin/pages')],
    ['label' => 'Harmonização Facial']
];
ob_start();
?>

<form method="POST" action="<?= url('/admin/pages/harmonization') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- 1. TOPO DA PÁGINA -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="layout" size="18" style="color: var(--pastel-blue-accent);"></i> 1. Cabeçalho Principal (Header)
      </h3>
    </div>

    <div class="form-group">
      <label for="harmonization_title">Título da Página *</label>
      <input type="text" id="harmonization_title" name="harmonization_title" class="form-control" required value="<?= htmlspecialchars($settings['harmonization_title'] ?? 'Harmonização Facial Full Face') ?>">
    </div>

    <div class="form-group">
      <label for="harmonization_subtitle">Subtítulo / Descrição de Destaque *</label>
      <textarea id="harmonization_subtitle" name="harmonization_subtitle" class="form-control" rows="2" required><?= htmlspecialchars($settings['harmonization_subtitle'] ?? 'Planejamento arquitetônico completo do rosto para realçar sua beleza natural com máxima sofisticação, rejuvenescimento e elegância.') ?></textarea>
    </div>

    <div class="form-group">
      <label for="harmonization_intro">Texto de Introdução / Chamada Superior</label>
      <textarea id="harmonization_intro" name="harmonization_intro" class="form-control" rows="2"><?= htmlspecialchars($settings['harmonization_intro'] ?? 'Realce a sua beleza natural e resgate a sua autoconfiança com um planejamento tridimensional completo do seu rosto. Um rejuvenescimento global, mantendo sempre a sua essência.') ?></textarea>
    </div>
  </div>

  <!-- 2. SEÇÃO CONCEITUAL: O QUE É FULL FACE -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="sparkles" size="18" style="color: var(--pastel-green-accent);"></i> 2. Conceito Anatômico & Metodologia
      </h3>
    </div>

    <div class="form-group">
      <label for="harmonization_section_title">Título da Seção Explicativa *</label>
      <input type="text" id="harmonization_section_title" name="harmonization_section_title" class="form-control" required value="<?= htmlspecialchars($settings['harmonization_section_title'] ?? 'O que é a Harmonização Full Face?') ?>">
    </div>

    <div class="form-group">
      <label for="harmonization_p1">Primeiro Bloco do Texto *</label>
      <textarea id="harmonization_p1" name="harmonization_p1" class="form-control" rows="3" required><?= htmlspecialchars($settings['harmonization_p1'] ?? 'A Harmonização Facial Full Face é um conjunto estruturado de procedimentos médicos e biomédicos realizados com o objetivo de equilibrar e realçar os traços faciais, proporcionando uma aparência mais harmônica, descansada e rejuvenescida.') ?></textarea>
    </div>

    <div class="form-group">
      <label for="harmonization_p2">Segundo Bloco do Texto (Metodologia Dr. George Scapin) *</label>
      <textarea id="harmonization_p2" name="harmonization_p2" class="form-control" rows="3" required><?= htmlspecialchars($settings['harmonization_p2'] ?? 'Ao invés de tratar apenas uma linha ou sulco isolado, o Dr. George Scapin analisa proporções áureas, sustentação óssea e compartimentos de gordura profunda para entregar um resultado coeso, sofisticado e imperceptível aos olhos de terceiros.') ?></textarea>
    </div>

    <!-- Diferenciais em 2 Colunas -->
    <div class="admin-grid-2col" style="gap: 20px; margin-top: 15px;">
      <div style="background: var(--bg-surface-hover); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
        <label style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px; display: block;">Diferencial 1 (Título & Texto)</label>
        <input type="text" name="harmonization_feature1_title" class="form-control" style="margin-bottom: 8px;" value="<?= htmlspecialchars($settings['harmonization_feature1_title'] ?? 'Precisão Milimétrica') ?>" placeholder="Título">
        <textarea name="harmonization_feature1_text" class="form-control" rows="2" placeholder="Descrição"><?= htmlspecialchars($settings['harmonization_feature1_text'] ?? 'Pontos de aplicação calculados com base na anatomia tridimensional do seu rosto.') ?></textarea>
      </div>

      <div style="background: var(--bg-surface-hover); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
        <label style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px; display: block;">Diferencial 2 (Título & Texto)</label>
        <input type="text" name="harmonization_feature2_title" class="form-control" style="margin-bottom: 8px;" value="<?= htmlspecialchars($settings['harmonization_feature2_title'] ?? 'Padrão Ouro') ?>" placeholder="Título">
        <textarea name="harmonization_feature2_text" class="form-control" rows="2" placeholder="Descrição"><?= htmlspecialchars($settings['harmonization_feature2_text'] ?? 'Ácido hialurônico e bioestimuladores das marcas líderes mundiais.') ?></textarea>
      </div>
    </div>
  </div>

  <!-- 3. FOTO DE DESTAQUE DA PÁGINA -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="image" size="18" style="color: var(--pastel-amber-accent);"></i> 3. Imagem Ilustrativa de Destaque
      </h3>
    </div>

    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
      <?php $curImg = $settings['harmonization_image'] ?? '/assets/fullface.png'; ?>
      <div style="text-align: center; background: var(--bg-surface-hover); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); max-width: 220px;">
        <div style="font-size: 0.75rem; color: var(--pastel-blue-text); font-weight: 600; margin-bottom: 8px;">Imagem Atual</div>
        <img src="<?= asset($curImg) ?>" alt="Harmonização Full Face" style="width: 180px; height: 160px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-light); display: block; margin: 0 auto 8px;">
        <p style="font-size: 0.7rem; color: var(--text-muted); word-break: break-all; margin: 0;"><?= htmlspecialchars($curImg) ?></p>
      </div>

      <div style="flex: 1; min-width: 260px;">
        <label for="harmonization_image_file" style="font-size: 0.9rem; color: var(--text-primary); margin-bottom: 8px; display: block; font-weight: 500;">
          Substituir por outra imagem:
        </label>
        <input type="file" id="harmonization_image_file" name="harmonization_image_file" class="form-control" accept="image/*">
        <p class="form-hint" style="margin-top: 6px;">Formatos recomendados: JPG, PNG ou WebP. A imagem será otimizada automaticamente.</p>
      </div>
    </div>
  </div>

  <!-- 4. BANNER FINAL DE CTA / CONVERSÃO -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="calendar" size="18" style="color: var(--pastel-purple-accent);"></i> 4. Chamada para Agendamento Final (CTA)
      </h3>
    </div>

    <div class="form-group">
      <label for="harmonization_cta_title">Título da Chamada Final</label>
      <input type="text" id="harmonization_cta_title" name="harmonization_cta_title" class="form-control" value="<?= htmlspecialchars($settings['harmonization_cta_title'] ?? 'Pronto para transformar sua autoestima?') ?>">
    </div>

    <div class="form-group">
      <label for="harmonization_cta_text">Texto de Apoio da Chamada Final</label>
      <textarea id="harmonization_cta_text" name="harmonization_cta_text" class="form-control" rows="2"><?= htmlspecialchars($settings['harmonization_cta_text'] ?? 'Agende uma consulta presencial na clínica em Porto Alegre e descubra o que a estética de alta performance pode fazer por você.') ?></textarea>
    </div>

    <div class="form-group">
      <label for="harmonization_cta_btn">Texto do Botão de Agendamento</label>
      <input type="text" id="harmonization_cta_btn" name="harmonization_cta_btn" class="form-control" value="<?= htmlspecialchars($settings['harmonization_cta_btn'] ?? 'Agendar Consulta com Dr. George') ?>">
    </div>
  </div>

  <div style="display: flex; gap: 12px; margin-top: 25px; margin-bottom: 50px;">
    <button type="submit" class="btn-admin btn-primary" style="padding: 12px 30px;">
      <i data-lucide="save" size="16"></i> Salvar Conteúdo da Harmonização
    </button>
    <a href="<?= url('/admin/pages') ?>" class="btn-admin btn-secondary" style="padding: 12px 20px;">Cancelar</a>
  </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
