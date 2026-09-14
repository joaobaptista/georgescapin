<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header" style="padding: 140px 5% 50px;">
    <p style="color: var(--gold-primary); text-transform: uppercase; letter-spacing: 2px; font-size: 0.9rem; margin-bottom: 15px;">
      Publicado por <?= htmlspecialchars($post['author'] ?? 'Dr. George') ?> &bull; <?= date('d/m/Y', strtotime($post['created_at'])) ?>
    </p>
    <h1 style="font-size: 3rem; line-height: 1.2; max-width: 900px; margin: 0 auto;"><?= htmlspecialchars($post['title']) ?></h1>
  </div>

  <article style="max-width: 850px; margin: 0 auto; padding: 0 5% 100px;">
    <?php if (!empty($post['image_url'])): ?>
      <img src="<?= asset($post['image_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width: 100%; border-radius: 12px; margin-bottom: 40px; aspect-ratio: 16/9; object-fit: cover; border: 1px solid var(--card-border);">
    <?php endif; ?>

    <div class="post-content" style="color: var(--text-main); font-size: 1.15rem; line-height: 1.9;">
      <?= $post['content'] ?>
    </div>

    <!-- CTA do Post -->
    <div style="background: var(--bg-light); border: 1px solid var(--card-border); border-radius: 10px; padding: 40px; text-align: center; margin-top: 60px;">
      <h3 style="color: var(--gold-light); font-size: 1.8rem; margin-bottom: 10px; font-family: var(--font-serif);"><?= htmlspecialchars($settings['blog_cta_title'] ?? 'Gostou do conteúdo?') ?></h3>
      <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 25px;"><?= nl2br(htmlspecialchars($settings['blog_cta_text'] ?? 'Agende uma consulta de avaliação personalizada com o Dr. George Scapin em Porto Alegre.')) ?></p>
      <a href="<?= url('/contato') ?>" class="btn-primary btn-solid">
        Agendar Consulta <i data-lucide="calendar" size="18"></i>
      </a>
    </div>

    <div style="margin-top: 40px; text-align: center;">
      <a href="<?= url('/blog') ?>" class="btn-primary">
        <i data-lucide="arrow-left" size="16"></i> Voltar para o Blog
      </a>
    </div>
  </article>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
