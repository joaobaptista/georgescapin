<!DOCTYPE html>
<html lang="pt-br" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Administrativo | Dr. George Scapin CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
  <!-- Favicon Admin -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
  <style>
    [data-theme="light"] body {
      background: #f1f5f9 !important;
    }
    [data-theme="light"] .login-box {
      background: #ffffff !important;
      border: 1px solid rgba(156, 122, 49, 0.2) !important;
      box-shadow: 0 10px 40px rgba(0,0,0,0.06) !important;
    }
    [data-theme="light"] .login-box h2 {
      color: var(--gold-primary) !important;
    }
    [data-theme="light"] .form-control {
      background-color: #ffffff !important;
      border-color: #cbd5e1 !important;
      color: #0f172a !important;
    }
    [data-theme="light"] .form-group label {
      color: #0f172a !important;
    }
  </style>
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#060907;padding:20px;position:relative;">

  <!-- Theme Toggle Floating -->
  <button id="themeToggle" aria-label="Alternar Tema" title="Alternar Tema" style="position:absolute;top:25px;right:25px;background:none;border:1px solid var(--card-border);color:var(--gold-primary);cursor:pointer;display:flex;align-items:center;justify-content:center;padding:8px;border-radius:50%;width:40px;height:40px;">
    <i data-lucide="sun" size="20"></i>
  </button>

  <div class="login-box" style="width:100%;max-width:420px;background:#121814;border:1px solid var(--card-border);padding:40px;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.5);">
    <div style="text-align:center;margin-bottom:30px;">
      <div class="logo-svg" style="background-image: url('<?= asset('assets/images/logo.svg') ?>'); background-position: center; margin: 0 auto; width: 220px; height: 50px;"></div>
      <h2 style="font-size:1.4rem;color:var(--gold-light);margin-top:15px;">Acesso ao CMS</h2>
      <p style="color:var(--text-muted);font-size:0.85rem;">Gerencie tratamentos, leads e conteúdos</p>
    </div>

    <?php if ($msg = flash('error')): ?>
      <div style="background:rgba(239,68,68,0.15);border:1px solid #ef4444;color:#ef4444;padding:12px;border-radius:6px;margin-bottom:20px;font-size:0.85rem;text-align:center;">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
      <div style="background:rgba(37,211,102,0.15);border:1px solid #25D366;color:#25D366;padding:12px;border-radius:6px;margin-bottom:20px;font-size:0.85rem;text-align:center;">
        <?= htmlspecialchars($msg) ?>
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

      <button type="submit" class="btn-primary btn-solid" style="width:100%;justify-content:center;margin-top:20px;">
        Entrar no Sistema <i data-lucide="log-in" size="18"></i>
      </button>
    </form>

    <div style="text-align:center;margin-top:25px;">
      <a href="<?= url('/') ?>" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">← Voltar ao site</a>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('themeToggle');
    function updateIcon() {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (themeToggleBtn) {
        themeToggleBtn.innerHTML = isDark ? '<i data-lucide="sun" size="20"></i>' : '<i data-lucide="moon" size="20"></i>';
        lucide.createIcons();
      }
    }
    updateIcon();
    
    if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', () => {
        let current = document.documentElement.getAttribute('data-theme');
        let next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateIcon();
      });
    }
  </script>
</body>
</html>
