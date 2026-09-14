<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header">
    <h1><?= htmlspecialchars($clinic['title'] ?? 'Dr. George Scapin') ?></h1>
    <p><?= $clinic['subtitle'] ?? 'Biomédico Esteta | CRBM 5202' ?></p>
  </div>

  <section class="excellence" style="padding-top: 80px; padding-bottom: 80px;">
    <img src="<?= asset($clinic['image_url'] ?: 'assets/img/drgeorge.jpeg') ?>" alt="Dr. George Scapin" class="excellence-img" style="border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div class="excellence-content">
      <h2>Ciência, Precisão e<br>Sensibilidade Artística.</h2>
      <div style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 20px; color: var(--text-main);">
        <?= $clinic['paragraph_1'] ?? '' ?>
      </div>
      <div style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 25px; color: var(--text-main);">
        <?= $clinic['paragraph_2'] ?? '' ?>
      </div>
      
      <div style="background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--gold-primary); padding: 15px 20px; border-radius: 0 6px 6px 0; margin-bottom: 30px;">
        <span class="highlight" style="margin: 0; font-size: 1.15rem;">"<?= htmlspecialchars($clinic['highlight_quote'] ?? 'A verdadeira elegância está na naturalidade.') ?>"</span>
      </div>

      <a href="<?= url('/contato') ?>" class="btn-primary">
        Agendar Consulta com Dr. George <i data-lucide="arrow-right" size="18"></i>
      </a>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
