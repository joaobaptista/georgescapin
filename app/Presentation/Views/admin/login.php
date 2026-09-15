<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acessar Painel - Dr. George Scapin</title>
  
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <!-- Google Fonts: Roboto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= asset('assets/css/admin-design-system.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>

  <script>
    const savedAdminTheme = localStorage.getItem('admin_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedAdminTheme);
  </script>

  <style>
    body.login-page {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background-color: var(--bg-canvas);
      padding: 20px;
      position: relative;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--bg-surface);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      box-shadow: var(--shadow-md);
    }
    .login-logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .login-logo .logo-svg {
      background-image: url('<?= asset('assets/images/logo.svg') ?>');
      background-position: center;
      background-size: contain;
      background-repeat: no-repeat;
      margin: 0 auto;
      width: 220px;
      height: 48px;
    }
    .login-title {
      text-align: center;
      font-size: 1.35rem;
      margin-bottom: 6px;
      color: var(--text-primary);
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    .login-subtitle {
      text-align: center;
      font-size: 0.88rem;
      color: var(--text-muted);
      margin-bottom: 25px;
    }
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .password-wrapper input {
      padding-right: 42px;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .toggle-password:hover {
      color: var(--text-primary);
    }
    .login-footer {
      margin-top: 24px;
      text-align: center;
      border-top: 1px solid var(--border-subtle);
      padding-top: 18px;
      font-size: 0.88rem;
      color: var(--text-muted);
    }
  </style>
</head>
<body class="login-page">

  <!-- Theme Toggle Floating -->
  <button id="themeToggle" class="btn-icon" aria-label="Alternar Tema" title="Alternar Tema" style="position:absolute;top:25px;right:25px;box-shadow:var(--shadow-sm);">
    <i id="themeIcon" data-lucide="moon" size="18"></i>
  </button>

  <div class="login-card">
    <div class="login-logo">
      <div class="logo-svg"></div>
    </div>
    
    <h2 class="login-title">Acessar Painel CMS</h2>
    <p class="login-subtitle">Gestão de Tratamentos & Conteúdos</p>

    <?php if ($msg = flash('error')): ?>
      <div style="background: var(--pastel-red-bg); border: 1px solid var(--pastel-red-border); color: var(--pastel-red-text); padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="alert-circle" size="18"></i> <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
      <div style="background: var(--pastel-green-bg); border: 1px solid var(--pastel-green-border); color: var(--pastel-green-text); padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="check-circle" size="18"></i> <span><?= htmlspecialchars($msg) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/login') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">E-mail de Acesso</label>
        <input type="email" id="email" name="email" class="form-control" required placeholder="admin@drgeorgescapin.com.br" value="admin@drgeorgescapin.com.br" autofocus>
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" value="admin123">
          <button type="button" class="toggle-password" id="togglePasswordBtn" aria-label="Mostrar ou ocultar senha">
            <i id="togglePasswordIcon" data-lucide="eye" size="18"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;margin-top:24px;padding:12px;font-weight:600;font-size:0.95rem;">
        <i data-lucide="log-in" size="18"></i> Entrar no Sistema
      </button>
    </form>

    <div class="login-footer">
      <a href="<?= url('/') ?>" style="color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
        <i data-lucide="arrow-left" size="15"></i> Voltar para o site público
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

    // Toggle Password Visibility
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    if (togglePasswordBtn && passwordInput) {
      togglePasswordBtn.addEventListener('click', () => {
        const isPass = passwordInput.type === 'password';
        passwordInput.type = isPass ? 'text' : 'password';
        togglePasswordBtn.innerHTML = isPass ? '<i data-lucide="eye-off" size="18"></i>' : '<i data-lucide="eye" size="18"></i>';
        lucide.createIcons();
      });
    }

    lucide.createIcons();
  </script>
</body>
</html>
