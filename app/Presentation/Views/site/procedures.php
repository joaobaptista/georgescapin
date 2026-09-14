<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header" style="padding: 140px 5% 60px;">
    <h1><?= htmlspecialchars($settings['procedures_page_title'] ?? 'Nossos Procedimentos') ?></h1>
    <p><?= htmlspecialchars($settings['procedures_page_subtitle'] ?? 'Conheça os procedimentos desenhados para realçar o que você tem de melhor, com foco absoluto em proporção geométrica e elegância.') ?></p>
  </div>

  <section style="max-width: 100%; margin: 0 auto; padding: 0;">
    <?php foreach ($procedures as $idx => $proc): ?>
      <?php $isEven = ($idx % 2 === 1); ?>
      <div style="display: flex; flex-wrap: wrap; align-items: stretch; width: 100%; border-bottom: 1px solid var(--card-border);">
        
        <div style="flex: 1 1 50%; min-width: 320px; order: <?= $isEven ? '2' : '1' ?>;">
          <img src="<?= asset($proc['image_url'] ?: 'assets/images/botox.png') ?>" alt="<?= htmlspecialchars($proc['title']) ?>" style="width: 100%; height: 100%; min-height: 420px; max-height: 550px; object-fit: cover; display: block;">
        </div>

        <div style="flex: 1 1 50%; min-width: 320px; order: <?= $isEven ? '1' : '2' ?>; display: flex; flex-direction: column; justify-content: center; padding: 8% 7%; background-color: var(--bg-light);">
          <div style="color: var(--gold-primary); margin-bottom: 15px;">
            <i data-lucide="<?= htmlspecialchars($proc['icon_name'] ?: 'sparkles') ?>" size="36"></i>
          </div>
          <h2 style="font-family: var(--font-serif); font-size: 2.4rem; color: var(--gold-light); margin-bottom: 20px; line-height: 1.2;">
            <?= htmlspecialchars($proc['title']) ?>
          </h2>
          <p style="color: var(--text-main); font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px;">
            <?= nl2br(htmlspecialchars($proc['short_description'])) ?>
          </p>
          <?php if (!empty($proc['full_description'])): ?>
            <div style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.8; margin-bottom: 30px;">
              <?= $proc['full_description'] ?>
            </div>
          <?php endif; ?>
          <div>
            <a href="<?= url('/contato') ?>" class="btn-primary">
              Agendar Avaliação <i data-lucide="calendar" size="16"></i>
            </a>
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
