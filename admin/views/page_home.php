<?php
$pageTitle = 'Editar Conteúdo: Página Inicial (Home)';
ob_start();
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">Página Inicial (Home /)</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Edite os textos do Hero, frases de efeito e banners de fundo para o modo claro e escuro.</p>
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
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 30px;">
      <!-- Banner Modo Escuro -->
      <div style="background: rgba(197, 160, 89, 0.06); padding: 20px; border-radius: 10px; border: 1px solid var(--card-border);">
        <label style="font-weight: 600; font-size: 1rem; color: var(--gold-light); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i data-lucide="moon" size="18"></i> Imagem Hero (Modo Escuro)
        </label>
        
        <?php if (!empty($hero['bg_image_dark'])): ?>
          <img src="<?= asset($hero['bg_image_dark']) ?>" alt="Hero Dark" style="width: 100%; height: 130px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 12px; display: block;">
        <?php endif; ?>

        <input type="file" id="bg_dark_file" name="bg_dark_file" class="form-control" accept="image/*">
        <small style="color: var(--text-muted); display: block; margin-top: 6px;">Conversão automática para WebP otimizado.</small>
      </div>

      <!-- Banner Modo Claro -->
      <div style="background: rgba(197, 160, 89, 0.06); padding: 20px; border-radius: 10px; border: 1px solid var(--card-border);">
        <label style="font-weight: 600; font-size: 1rem; color: var(--gold-light); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i data-lucide="sun" size="18"></i> Imagem Hero (Modo Claro)
        </label>
        
        <?php if (!empty($hero['bg_image_light'])): ?>
          <img src="<?= asset($hero['bg_image_light']) ?>" alt="Hero Light" style="width: 100%; height: 130px; object-fit: cover; border-radius: 6px; border: 1px solid var(--card-border); margin-bottom: 12px; display: block;">
        <?php endif; ?>

        <input type="file" id="bg_light_file" name="bg_light_file" class="form-control" accept="image/*">
        <small style="color: var(--text-muted); display: block; margin-top: 6px;">Conversão automática para WebP otimizado.</small>
      </div>
    </div>

    <div style="display: flex; gap: 15px; margin-top: 35px;">
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
