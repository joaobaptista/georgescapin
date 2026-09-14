<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header">
    <h1><?= htmlspecialchars(!empty($clinic['title']) ? $clinic['title'] : 'Dr. George Scapin') ?></h1>
    <p><?= !empty($clinic['subtitle']) ? $clinic['subtitle'] : 'Biomédico Esteta | CRBM 5202<br>Especialista em Harmonização Facial Avançada' ?></p>
  </div>

  <section class="excellence" style="padding-top: 80px; padding-bottom: 80px;">
    <img src="<?= asset(!empty($clinic['image_url']) ? $clinic['image_url'] : 'assets/img/drgeorge.jpeg') ?>" alt="Dr. George Scapin" class="excellence-img" style="border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div class="excellence-content">
      <h2><?= !empty($settings['clinic_section_title']) ? $settings['clinic_section_title'] : 'Ciência, Precisão e<br>Sensibilidade Artística.' ?></h2>
      <div style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 20px; color: var(--text-main);">
        <?= !empty($clinic['paragraph_1']) ? $clinic['paragraph_1'] : 'Com formação sólida e dedicação exclusiva à estética avançada e ao gerenciamento do envelhecimento, o Dr. George Scapin combina conhecimento anatômico aprofundado e visão artística refinada.' ?>
      </div>
      <div style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 25px; color: var(--text-main);">
        <?= !empty($clinic['paragraph_2']) ? $clinic['paragraph_2'] : 'Cada paciente recebe um plano de tratamento 360° totalmente individualizado, utilizando produtos de padrão ouro e tecnologias modernas para proporcionar rejuvenescimento seguro, duradouro e com máxima naturalidade.' ?>
      </div>
      
      <?php $quote = !empty($clinic['highlight_quote']) ? $clinic['highlight_quote'] : 'A verdadeira elegância está na naturalidade e no respeito aos seus traços.'; ?>
      <div style="background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--gold-primary); padding: 15px 20px; border-radius: 0 6px 6px 0; margin-bottom: 30px;">
        <span class="highlight" style="margin: 0; font-size: 1.15rem;">"<?= htmlspecialchars($quote) ?>"</span>
      </div>

      <a href="<?= url('/contato') ?>" class="btn-primary">
        <?= htmlspecialchars(!empty($settings['clinic_cta_btn']) ? $settings['clinic_cta_btn'] : 'Agendar Consulta com Dr. George') ?> <i data-lucide="arrow-right" size="18"></i>
      </a>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
