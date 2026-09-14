<?php
$pageTitle = 'Editar Conteúdo: Página Inicial (Home)';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Página Inicial (Home /)</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Edite os textos do Hero, 3 pilares, seção de tratamentos e bloco de queixas/dúvidas.</p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="<?= url('/') ?>" target="_blank" class="btn-primary" style="padding: 8px 16px;">
        <i data-lucide="external-link" size="16"></i> Ver Home ao Vivo
      </a>
      <a href="<?= url('/admin/pages') ?>" class="btn-primary" style="padding: 8px 16px;">
        <i data-lucide="arrow-left" size="16"></i> Voltar às Páginas
      </a>
    </div>
  </div>

  <form method="POST" action="<?= url('/admin/pages/home') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- 1. SEÇÃO HERO PRINCIPAL -->
    <div style="background: rgba(197, 160, 89, 0.04); border: 1px solid var(--card-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
      <h4 style="color: var(--gold-light); font-size: 1.05rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="layout" size="18"></i> 1. Hero Principal (Topo da Home)
      </h4>

      <div class="form-group">
        <label for="hero_title">Título Principal (Hero Title) *</label>
        <input type="text" id="hero_title" name="hero_title" class="form-control" required value="<?= htmlspecialchars($hero['title'] ?? '') ?>" placeholder="Ex: A Arte da Precisão Facial">
        <small style="color: var(--text-muted);">Dica: Você pode usar &lt;br&gt; para quebrar a linha esteticamente.</small>
      </div>

      <div class="form-group">
        <label for="hero_subtitle">Subtítulo / Proposta de Valor *</label>
        <textarea id="hero_subtitle" name="hero_subtitle" class="form-control" rows="3" required placeholder="Texto complementar que destaca a excelência e sofisticação"><?= htmlspecialchars($hero['subtitle'] ?? '') ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
          <label for="hero_button_text">Texto do Botão Principal (CTA)</label>
          <input type="text" id="hero_button_text" name="hero_button_text" class="form-control" value="<?= htmlspecialchars($hero['button_text'] ?? 'Agendar Consulta') ?>">
        </div>

        <div class="form-group">
          <label for="hero_button_link">Link do Botão Principal</label>
          <input type="text" id="hero_button_link" name="hero_button_link" class="form-control" value="<?= htmlspecialchars($hero['button_link'] ?? '/contato') ?>">
        </div>
      </div>

      <!-- BANNERS DE FUNDO (DARK E LIGHT) -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 20px;">
        <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; border: 1px solid var(--card-border);">
          <label style="font-weight: 600; font-size: 0.95rem; color: var(--gold-light); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="moon" size="16"></i> Imagem Hero (Modo Escuro)
          </label>
          <?php if (!empty($hero['bg_image_dark'])): ?>
            <img src="<?= asset($hero['bg_image_dark']) ?>" alt="Hero Dark" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 10px; display: block;">
          <?php endif; ?>
          <input type="file" id="bg_dark_file" name="bg_dark_file" class="form-control" accept="image/*">
        </div>

        <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; border: 1px solid var(--card-border);">
          <label style="font-weight: 600; font-size: 0.95rem; color: var(--gold-light); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="sun" size="16"></i> Imagem Hero (Modo Claro)
          </label>
          <?php if (!empty($hero['bg_image_light'])): ?>
            <img src="<?= asset($hero['bg_image_light']) ?>" alt="Hero Light" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 10px; display: block;">
          <?php endif; ?>
          <input type="file" id="bg_light_file" name="bg_light_file" class="form-control" accept="image/*">
        </div>
      </div>
    </div>

    <!-- 2. SEÇÃO DOS 3 PILARES -->
    <div style="background: rgba(197, 160, 89, 0.04); border: 1px solid var(--card-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
      <h4 style="color: var(--gold-light); font-size: 1.05rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="layers" size="18"></i> 2. Seção dos 3 Pilares de Atendimento
      </h4>

      <!-- Pilar 1 -->
      <div style="background: var(--bg-light); padding: 15px; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 15px;">
        <label style="font-weight: 600; color: var(--gold-light); margin-bottom: 6px; display: block;">Pilar 1 (Título & Descrição)</label>
        <input type="text" name="home_pillar1_title" class="form-control" style="margin-bottom: 8px;" value="<?= htmlspecialchars($settings['home_pillar1_title'] ?? '01. Planejamento 360') ?>" placeholder="Ex: 01. Planejamento 360">
        <textarea name="home_pillar1_text" class="form-control" rows="2" placeholder="Descrição do pilar"><?= htmlspecialchars($settings['home_pillar1_text'] ?? 'Entender os desejos de cada paciente para indicar os melhores tratamentos, garantindo um acompanhamento próximo e resultados satisfatórios.') ?></textarea>
      </div>

      <!-- Pilar 2 -->
      <div style="background: var(--bg-light); padding: 15px; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 15px;">
        <label style="font-weight: 600; color: var(--gold-light); margin-bottom: 6px; display: block;">Pilar 2 (Título & Descrição)</label>
        <input type="text" name="home_pillar2_title" class="form-control" style="margin-bottom: 8px;" value="<?= htmlspecialchars($settings['home_pillar2_title'] ?? '02. Cuidado Personalizado') ?>" placeholder="Ex: 02. Cuidado Personalizado">
        <textarea name="home_pillar2_text" class="form-control" rows="2" placeholder="Descrição do pilar"><?= htmlspecialchars($settings['home_pillar2_text'] ?? 'Descubra a excelência em cuidados estéticos personalizados, onde utilizo as melhores técnicas para proporcionar resultados eficazes, seguros e adaptados às suas necessidades únicas.') ?></textarea>
      </div>

      <!-- Pilar 3 -->
      <div style="background: var(--bg-light); padding: 15px; border-radius: 6px; border: 1px solid var(--card-border);">
        <label style="font-weight: 600; color: var(--gold-light); margin-bottom: 6px; display: block;">Pilar 3 (Título & Descrição)</label>
        <input type="text" name="home_pillar3_title" class="form-control" style="margin-bottom: 8px;" value="<?= htmlspecialchars($settings['home_pillar3_title'] ?? '03. Rejuvenescimento') ?>" placeholder="Ex: 03. Rejuvenescimento">
        <textarea name="home_pillar3_text" class="form-control" rows="2" placeholder="Descrição do pilar"><?= htmlspecialchars($settings['home_pillar3_text'] ?? 'Sua aparência deve acompanhar como a sua mente se sente. Começamos a intervenção no momento certo, permitindo gerenciar o tempo com total naturalidade.') ?></textarea>
      </div>
    </div>

    <!-- 3. SEÇÃO DE TRATAMENTOS (CABEÇALHO) -->
    <div style="background: rgba(197, 160, 89, 0.04); border: 1px solid var(--card-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
      <h4 style="color: var(--gold-light); font-size: 1.05rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="sparkles" size="18"></i> 3. Chamada da Seção de Tratamentos em Destaque
      </h4>

      <div class="form-group">
        <label for="home_services_title">Título da Seção de Procedimentos</label>
        <input type="text" id="home_services_title" name="home_services_title" class="form-control" value="<?= htmlspecialchars($settings['home_services_title'] ?? 'Naturalidade, Ciência e Bem-Estar') ?>">
      </div>

      <div class="form-group">
        <label for="home_services_subtitle">Subtítulo / Descrição</label>
        <input type="text" id="home_services_subtitle" name="home_services_subtitle" class="form-control" value="<?= htmlspecialchars($settings['home_services_subtitle'] ?? 'Procedimentos focados em realçar a sua beleza natural com sofisticação inigualável.') ?>">
      </div>
    </div>

    <!-- 4. BANNER DE DÚVIDAS / QUEIXAS (FAQ CTA) -->
    <div style="background: rgba(197, 160, 89, 0.04); border: 1px solid var(--card-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
      <h4 style="color: var(--gold-light); font-size: 1.05rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="help-circle" size="18"></i> 4. Bloco de Dúvidas / Queixas (CTA Inferior da Home)
      </h4>

      <div class="form-group">
        <label for="home_faq_tag">Tag Superior (Texto Pequeno)</label>
        <input type="text" id="home_faq_tag" name="home_faq_tag" class="form-control" value="<?= htmlspecialchars($settings['home_faq_tag'] ?? 'Possui alguma dúvida?') ?>">
      </div>

      <div class="form-group">
        <label for="home_faq_title">Título das Queixas</label>
        <input type="text" id="home_faq_title" name="home_faq_title" class="form-control" value="<?= htmlspecialchars($settings['home_faq_title'] ?? 'Flacidez? Bigode Chinês? Pés de Galinha? Cicatrizes de Acne?') ?>">
      </div>

      <div class="form-group">
        <label for="home_faq_subtitle">Texto Explicativo</label>
        <textarea id="home_faq_subtitle" name="home_faq_subtitle" class="form-control" rows="2"><?= htmlspecialchars($settings['home_faq_subtitle'] ?? 'Para toda queixa existe uma alternativa e possibilidade de tratamento. Estou aqui para te ouvir.') ?></textarea>
      </div>

      <div class="form-group">
        <label for="home_faq_btn">Texto do Botão de Agendamento</label>
        <input type="text" id="home_faq_btn" name="home_faq_btn" class="form-control" value="<?= htmlspecialchars($settings['home_faq_btn'] ?? 'Agende sua consulta') ?>">
      </div>
    </div>

    <div style="display: flex; gap: 15px; margin-top: 30px;">
      <button type="submit" class="btn-primary btn-solid" style="padding: 12px 35px;">
        <i data-lucide="save" size="18"></i> Salvar Conteúdo da Home
      </button>
      <a href="<?= url('/admin/pages') ?>" class="btn-primary" style="padding: 12px 25px;">Cancelar</a>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
