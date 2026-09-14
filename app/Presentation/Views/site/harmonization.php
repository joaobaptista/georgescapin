<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <!-- Cabeçalho Padrão da Página -->
  <div class="page-header">
    <h1>Harmonização Facial Full Face</h1>
    <p>Planejamento arquitetônico completo do rosto para realçar sua beleza natural com máxima sofisticação, rejuvenescimento e elegância.</p>
  </div>

  <!-- Banner / Introdução com CTA -->
  <section style="padding: 70px 5% 60px; background-color: var(--bg-color); text-align: center; border-bottom: 1px solid var(--card-border);">
    <div style="max-width: 850px; margin: 0 auto;">
      <p style="font-size: 1.2rem; color: var(--text-main); line-height: 1.8; margin-bottom: 30px;">
        Realce a sua beleza natural e resgate a sua autoconfiança com um planejamento tridimensional completo do seu rosto. Um rejuvenescimento global, mantendo sempre a sua essência.
      </p>
      <a href="<?= url('/contato') ?>" class="btn-primary btn-solid" style="padding: 14px 35px; font-size: 1rem;">
        Quero Minha Avaliação Full Face <i data-lucide="calendar" size="18"></i>
      </a>
    </div>
  </section>

  <!-- O que é Full Face -->
  <section style="padding: 90px 5%; background-color: var(--bg-light);">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 60px; align-items: center;">
      <div style="flex: 1 1 500px;">
        <h2 style="font-family: var(--font-serif); font-size: 2.8rem; color: var(--gold-light); margin-bottom: 25px; line-height: 1.2;">
          O que é a Harmonização Full Face?
        </h2>
        <p style="color: var(--text-main); font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px;">
          A Harmonização Facial Full Face é um conjunto estruturado de procedimentos médicos e biomédicos realizados com o objetivo de equilibrar e realçar os traços faciais, proporcionando uma aparência mais harmônica, descansada e rejuvenescida.
        </p>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.8; margin-bottom: 30px;">
          Ao invés de tratar apenas uma linha ou sulco isolado, o Dr. George Scapin analisa proporções áureas, sustentação óssea e compartimentos de gordura profunda para entregar um resultado coeso, sofisticado e imperceptível aos olhos de terceiros.
        </p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div style="border-left: 2px solid var(--gold-primary); padding-left: 15px;">
            <h4 style="color: var(--gold-light); font-family: var(--font-serif); font-size: 1.2rem; margin-bottom: 5px;">Precisão Milimétrica</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Pontos de aplicação calculados com base na anatomia tridimensional do seu rosto.</p>
          </div>
          <div style="border-left: 2px solid var(--gold-primary); padding-left: 15px;">
            <h4 style="color: var(--gold-light); font-family: var(--font-serif); font-size: 1.2rem; margin-bottom: 5px;">Padrão Ouro</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Ácido hialurônico e bioestimuladores das marcas líderes mundiais.</p>
          </div>
        </div>
      </div>
      <div style="flex: 1 1 400px;">
        <img src="<?= asset('assets/fullface.png') ?>" alt="Harmonização Facial Full Face" style="width: 100%; border-radius: 10px; border: 1px solid var(--card-border); box-shadow: 0 10px 40px rgba(0,0,0,0.4);">
      </div>
    </div>
  </section>

  <!-- CTA Final -->
  <section style="text-align: center; padding: 90px 5%; border-top: 1px solid var(--card-border);">
    <div style="max-width: 800px; margin: 0 auto;">
      <h2 style="font-family: var(--font-serif); font-size: 2.6rem; color: var(--gold-light); margin-bottom: 20px;">
        Pronto para transformar sua autoestima?
      </h2>
      <p style="color: var(--text-muted); font-size: 1.15rem; margin-bottom: 35px;">
        Agende uma consulta presencial na clínica em Porto Alegre e descubra o que a estética de alta performance pode fazer por você.
      </p>
      <a href="<?= url('/contato') ?>" class="btn-primary btn-solid" style="padding: 14px 35px; font-size: 1rem;">
        Agendar Consulta com Dr. George <i data-lucide="arrow-right" size="18"></i>
      </a>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
