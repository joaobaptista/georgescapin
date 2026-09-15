<!DOCTYPE html>
<html lang="pt-br" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Administrativo | Dr. George Scapin CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  
  <!-- Favicon Admin -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <link rel="stylesheet" href="<?= asset('assets/css/admin-design-system.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    const savedAdminTheme = localStorage.getItem('admin_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedAdminTheme);
  </script>
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background-color:var(--bg-app);padding:20px;position:relative;">

  <!-- Theme Toggle Floating -->
  <button id="themeToggle" aria-label="Alternar Tema" title="Alternar Tema" style="position:absolute;top:25px;right:25px;background:var(--bg-surface);border:1px solid var(--border-subtle);color:var(--text-secondary);cursor:pointer;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius-sm);box-shadow:var(--elevation-1);transition:all var(--transition-fast);">
    <i id="themeIcon" data-lucide="moon" size="18"></i>
  </button>

  <div class="admin-card" style="width:100%;max-width:420px;padding:40px;border-radius:var(--radius-lg);box-shadow:var(--elevation-3);margin-bottom:0;">
    <div style="text-align:center;margin-bottom:30px;">
      <div class="logo-svg" style="background-image: url('<?= asset('assets/images/logo.svg') ?>'); background-position: center; margin: 0 auto; width: 220px; height: 50px;"></div>
      <h2 style="font-size:1.35rem;font-weight:700;color:var(--text-main);margin-top:20px;letter-spacing:-0.01em;">Acesso ao CMS</h2>
      <p style="color:var(--text-muted);font-size:0.875rem;margin-top:4px;">Painel de Gestão e Conteúdo</p>
    </div>

    <?php if ($msg = flash('error')): ?>
      <div style="background:var(--color-error-bg);border:1px solid var(--color-error-border);color:var(--color-error);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:20px;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:8px;">
        <i data-lucide="alert-circle" size="18"></i> <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
      <div style="background:var(--color-success-bg);border:1px solid var(--color-success-border);color:var(--color-success);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:20px;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:8px;">
        <i data-lucide="check-circle" size="18"></i> <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/login') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" class="form-control" required placeholder="admin@drgeorgescapin.com.br" value="admin@drgeorgescapin.com.br">
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" value="admin123">
      </div>

      <button type="submit" class="btn-primary btn-solid" style="width:100%;justify-content:center;margin-top:24px;padding:12px;font-weight:600;font-size:0.9375rem;">
        Entrar no Sistema <i data-lucide="log-in" size="18"></i>
      </button>
    </form>

    <div style="text-align:center;margin-top:24px;padding-top:18px;border-top:1px solid var(--border-subtle);">
      <a href="<?= url('/') ?>" style="color:var(--text-muted);font-size:0.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color var(--transition-fast);">
        <i data-lucide="arrow-left" size="15"></i> Voltar ao site público
      </a>
    </div>
  </div>

  <script>
    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('themeToggle');
    function updateIcon() {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (themeToggleBtn) {
        themeToggleBtn.innerHTML = isDark ? '<i data-lucide="sun" size="18"></i>' : '<i data-lucide="moon" size="18"></i>';
        themeToggleBtn.setAttribute('title', isDark ? 'Alternar para Modo Claro' : 'Alternar para Modo Escuro');
        lucide.createIcons();
      }
    }
    updateIcon();
    
    if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', () => {
        let current = document.documentElement.getAttribute('data-theme') || 'light';
        let next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('admin_theme', next);
        updateIcon();
      });
    }

    lucide.createIcons();
  </script>
</body>
</html>
