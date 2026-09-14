<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header">
    <h1>Nossos Tratamentos</h1>
    <p>Conheça os procedimentos desenhados para realçar o que você tem de melhor, com foco absoluto em proporção geométrica e elegância.</p>
  </div>

  <section>
    <div class="procedures-grid">
      <?php foreach ($procedures as $proc): ?>
        <div class="procedure-card">
          <div class="procedure-img-wrapper">
            <img src="<?= asset($proc['image_url'] ?: 'assets/images/botox.png') ?>" alt="<?= htmlspecialchars($proc['title']) ?>" class="procedure-img">
          </div>
          <div class="procedure-content">
            <div class="procedure-icon"><i data-lucide="<?= htmlspecialchars($proc['icon_name'] ?: 'sparkles') ?>" size="32"></i></div>
            <h3><?= htmlspecialchars($proc['title']) ?></h3>
            <p><?= htmlspecialchars($proc['short_description']) ?></p>
            <?php if (!empty($proc['full_description'])): ?>
              <div style="margin-top: 15px; font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;"><?= $proc['full_description'] ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
