<?php
$pageTitle = ($customPage['title'] ?? 'Página') . ' | Dr. George Scapin';
$metaDescription = $customPage['meta_description'] ?? ($customPage['subtitle'] ?? '');
require __DIR__ . '/../layouts/site_header.php';
?>

<!-- Banner Superior -->
<section style="padding: 180px 5% 100px; text-align: center; position: relative; background: <?= !empty($customPage['banner_image']) ? "url('" . asset($customPage['banner_image']) . "') center center/cover no-repeat" : "var(--bg-light)" ?>;">
  <?php if (!empty($customPage['banner_image'])): ?>
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(6,9,7,0.78);"></div>
  <?php endif; ?>
  
  <div style="max-width: 900px; margin: 0 auto; position: relative; z-index: 1;">
    <h1 style="font-size: 3.2rem; color: var(--gold-light); margin-bottom: 20px; font-family: var(--font-serif); line-height: 1.2;">
      <?= htmlspecialchars($customPage['title']) ?>
    </h1>
    <?php if (!empty($customPage['subtitle'])): ?>
      <p style="font-size: 1.2rem; color: var(--text-main); line-height: 1.8; max-width: 750px; margin: 0 auto;">
        <?= $customPage['subtitle'] ?>
      </p>
    <?php endif; ?>
  </div>
</section>

<!-- Conteúdo Rico da Página -->
<section style="padding: 80px 5%; min-height: 400px; background-color: var(--bg-color);">
  <div style="max-width: 900px; margin: 0 auto; font-size: 1.1rem; line-height: 1.9; color: var(--text-main);">
    <div class="custom-page-body">
      <?= $customPage['content'] ?>
    </div>

    <div style="margin-top: 60px; padding: 40px; background: rgba(197, 160, 89, 0.08); border-radius: 12px; border: 1px solid var(--card-border); text-align: center;">
      <h3 style="font-family: var(--font-serif); color: var(--gold-light); font-size: 1.8rem; margin-bottom: 15px;">Deseja agendar uma consulta personalizada?</h3>
      <p style="color: var(--text-muted); margin-bottom: 25px;">Entre em contato com nossa equipe em Porto Alegre e descubra o tratamento ideal para você.</p>
      <a href="<?= url('/contato') ?>" class="btn-primary btn-solid" style="padding: 12px 35px;">
        Agendar Consulta com Dr. George <i data-lucide="arrow-right" size="18"></i>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
