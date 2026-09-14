<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div style="min-height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 160px 5% 100px;">
    <h1 style="font-size: 5rem; color: var(--gold-primary); font-family: var(--font-serif); margin-bottom: 10px;">404</h1>
    <h2 style="font-size: 2rem; color: var(--gold-light); margin-bottom: 20px;">Página Não Encontrada</h2>
    <p style="color: var(--text-muted); max-width: 500px; font-size: 1.1rem; margin-bottom: 35px;">
      O conteúdo que você procurava não existe, foi movido ou o link está incorreto.
    </p>
    <a href="<?= url('/') ?>" class="btn-primary">
      <i data-lucide="home" size="18"></i> Voltar para o Início
    </a>
  </div>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
