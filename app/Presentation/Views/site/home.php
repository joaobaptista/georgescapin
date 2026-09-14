<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-bg" style="background-image: var(--hero-bg-image);"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1><?= $hero['title'] ?? 'Estética Avançada,<br>Gerenciamento do Envelhecimento,<br>Harmonização Facial e Corporal.' ?></h1>
      <p><?= $hero['subtitle'] ?? 'Resultados que transcendem o tempo. Harmonização sofisticada com o rigor e a excelência que sua beleza merece.' ?></p>
      <a href="<?= url($hero['button_link'] ?? '/contato') ?>" class="btn-primary">
        <?= htmlspecialchars($hero['button_text'] ?? 'Agende sua consulta') ?>
        <i data-lucide="arrow-right" size="18"></i>
      </a>
    </div>
  </section>

  <!-- 3 Pillars Section -->
  <section style="background-color: var(--bg-light); border-top: 1px solid var(--card-border); border-bottom: 1px solid var(--card-border); padding: 80px 5%;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
      <div class="excellence-content" style="text-align: center; padding: 20px;">
        <h3 style="color: var(--gold-primary); font-size: 1.8rem; margin-bottom: 15px; font-family: var(--font-serif);">01. Planejamento 360</h3>
        <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.7;">Entender os desejos de cada paciente para indicar os melhores tratamentos, garantindo um acompanhamento próximo e resultados satisfatórios.</p>
      </div>
      <div class="excellence-content" style="text-align: center; padding: 20px;">
        <h3 style="color: var(--gold-primary); font-size: 1.8rem; margin-bottom: 15px; font-family: var(--font-serif);">02. Cuidado Personalizado</h3>
        <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.7;">Descubra a excelência em cuidados estéticos personalizados, onde utilizo as melhores técnicas para proporcionar resultados eficazes, seguros e adaptados às suas necessidades únicas.</p>
      </div>
      <div class="excellence-content" style="text-align: center; padding: 20px;">
        <h3 style="color: var(--gold-primary); font-size: 1.8rem; margin-bottom: 15px; font-family: var(--font-serif);">03. Rejuvenescimento</h3>
        <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.7;">Sua aparência deve acompanhar como a sua mente se sente. Começamos a intervenção no momento certo, permitindo gerenciar o tempo com total naturalidade.</p>
      </div>
    </div>
  </section>

  <!-- Services Grid -->
  <section>
    <div class="section-header">
      <h2>Naturalidade, Ciência e Bem-Estar</h2>
      <p>Procedimentos focados em realçar a sua beleza natural com sofisticação inigualável.</p>
    </div>

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
            <a href="<?= url('/procedimentos') ?>" class="procedure-link">
              Saiba mais <i data-lucide="chevron-right" size="16"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 50px;">
      <a href="<?= url('/procedimentos') ?>" class="btn-primary">Ver Todos os Procedimentos</a>
    </div>
  </section>

  <!-- Pain Points Section -->
  <section style="text-align: center; padding: 80px 5% 100px; background-color: var(--bg-light); border-top: 1px solid var(--card-border);">
    <div class="section-header" style="max-width: 850px; margin: 0 auto 40px auto;">
      <p style="color: var(--gold-primary); font-size: 1rem; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 2px;">Possui alguma dúvida?</p>
      <h2 style="font-size: 2.3rem; line-height: 1.3;">Flacidez? Bigode Chinês? Pés de Galinha? Cicatrizes de Acne?</h2>
      <p style="font-size: 1.15rem; color: var(--text-muted); margin-top: 20px;">Para toda queixa existe uma alternativa e possibilidade de tratamento. Estou aqui para te ouvir.</p>
    </div>
    <a href="<?= url('/contato') ?>" class="btn-primary btn-solid">
      Agende sua consulta
      <i data-lucide="calendar" size="18"></i>
    </a>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
