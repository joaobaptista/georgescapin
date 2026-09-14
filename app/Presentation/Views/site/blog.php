<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header" style="padding: 140px 5% 60px;">
    <h1><?= htmlspecialchars($settings['blog_page_title'] ?? 'Blog do Dr. George') ?></h1>
    <p><?= htmlspecialchars($settings['blog_page_subtitle'] ?? 'Artigos, dicas e novidades sobre Estética Avançada, Gerenciamento do Envelhecimento e Harmonização.') ?></p>
  </div>

  <section style="max-width: 1000px; margin: 0 auto; padding: 60px 5% 100px;">
    <div style="display: flex; flex-direction: column; gap: 40px;">
      <?php if (empty($posts)): ?>
        <p style="text-align: center; color: var(--text-muted); font-size: 1.1rem; padding: 50px;">Nenhum artigo publicado no momento.</p>
      <?php else: ?>
        <?php foreach ($posts as $p): ?>
          <article class="procedure-card" style="display: flex; flex-wrap: wrap; text-align: left; overflow: hidden; padding: 0;">
            <div style="flex: 1 1 320px; min-height: 250px;">
              <img src="<?= asset($p['image_url'] ?: 'assets/images/botox.png') ?>" alt="<?= htmlspecialchars($p['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="procedure-content" style="flex: 1 1 400px; padding: 40px; display: flex; flex-direction: column; justify-content: center;">
              <p style="color: var(--gold-primary); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 2px;">
                <?= htmlspecialchars($p['author'] ?? 'Dr. George') ?> &bull; <?= date('d/m/Y', strtotime($p['created_at'])) ?>
              </p>
              <h2 style="font-family: var(--font-serif); font-size: 1.8rem; color: var(--gold-light); margin-bottom: 15px; line-height: 1.3;">
                <a href="<?= url('/blog/' . $p['slug']) ?>" style="color: inherit; text-decoration: none;">
                  <?= htmlspecialchars($p['title']) ?>
                </a>
              </h2>
              <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 25px;">
                <?= htmlspecialchars($p['summary']) ?>
              </p>
              <div>
                <a href="<?= url('/blog/' . $p['slug']) ?>" class="btn-primary" style="display: inline-flex;">
                  Ler Artigo Completo <i data-lucide="chevron-right" size="16"></i>
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
          <nav aria-label="Navegação de páginas do Blog" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 50px;">
            
            <!-- Botão Anterior -->
            <?php if ($currentPage > 1): ?>
              <a href="<?= url('/blog?page=' . ($currentPage - 1)) ?>" class="btn-primary" style="padding: 10px 18px; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="chevron-left" size="16"></i> Anterior
              </a>
            <?php endif; ?>

            <!-- Números de Página -->
            <div style="display: flex; gap: 8px;">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $currentPage): ?>
                  <span style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; background: var(--gold-primary); color: #fff; font-weight: 600; border-radius: 4px; font-size: 0.95rem;">
                    <?= $i ?>
                  </span>
                <?php else: ?>
                  <a href="<?= url('/blog?page=' . $i) ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border: 1px solid var(--card-border); color: var(--text-main); text-decoration: none; border-radius: 4px; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--gold-primary)';this.style.color='var(--gold-primary)'" onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-main)'">
                    <?= $i ?>
                  </a>
                <?php endif; ?>
              <?php endfor; ?>
            </div>

            <!-- Botão Próximo -->
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= url('/blog?page=' . ($currentPage + 1)) ?>" class="btn-primary" style="padding: 10px 18px; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
                Próximo <i data-lucide="chevron-right" size="16"></i>
              </a>
            <?php endif; ?>

          </nav>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
