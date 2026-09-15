<!DOCTYPE html>
<html lang="pt-br" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> | Dr. George Scapin CMS</title>
  
  <!-- Google Fonts: Roboto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  
  <!-- Favicon Admin -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <!-- Admin Design System (Material 3 / Clean SaaS) -->
  <link rel="stylesheet" href="<?= asset('assets/css/admin-design-system.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <!-- Quill Rich Text Editor -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <script>
    const savedAdminTheme = localStorage.getItem('admin_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedAdminTheme);
  </script>
</head>
<body>

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <h2>Dr. George Scapin</h2>
      <span>Painel CMS</span>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-section">Principal</div>
      <a href="<?= url('/admin') ?>" class="<?= ($activeTab ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard" size="18"></i> Dashboard
      </a>
      <a href="<?= url('/admin/leads') ?>" class="<?= ($activeTab ?? '') === 'leads' ? 'active' : '' ?>">
        <i data-lucide="users" size="18"></i> Leads & Contatos
      </a>

      <div class="admin-nav-section">Conteúdo</div>
      <a href="<?= url('/admin/pages') ?>" class="<?= ($activeTab ?? '') === 'pages' ? 'active' : '' ?>">
        <i data-lucide="layout-template" size="18"></i> Páginas do Site
      </a>
      <a href="<?= url('/admin/procedures') ?>" class="<?= ($activeTab ?? '') === 'procedures' ? 'active' : '' ?>">
        <i data-lucide="sparkles" size="18"></i> Procedimentos
      </a>
      <a href="<?= url('/admin/posts') ?>" class="<?= ($activeTab ?? '') === 'posts' ? 'active' : '' ?>">
        <i data-lucide="newspaper" size="18"></i> Artigos do Blog
      </a>
      <a href="<?= url('/admin/menu') ?>" class="<?= ($activeTab ?? '') === 'menu' ? 'active' : '' ?>">
        <i data-lucide="menu" size="18"></i> Menu de Navegação
      </a>

      <div class="admin-nav-section">Configurações</div>
      <a href="<?= url('/admin/settings') ?>" class="<?= ($activeTab ?? '') === 'settings' ? 'active' : '' ?>">
        <i data-lucide="sliders" size="18"></i> Conteúdo Geral & SEO
      </a>
      <a href="<?= url('/admin/design-system') ?>" class="<?= ($activeTab ?? '') === 'design-system' ? 'active' : '' ?>">
        <i data-lucide="palette" size="18"></i> Design System
      </a>
      <a href="<?= url('/admin/profile') ?>" class="<?= ($activeTab ?? '') === 'profile' ? 'active' : '' ?>">
        <i data-lucide="shield-check" size="18"></i> Meu Perfil
      </a>
      <a href="<?= url('/') ?>" target="_blank" style="margin-top: 8px;">
        <i data-lucide="external-link" size="18"></i> Ver Site Público
      </a>
    </nav>
    <div style="padding: 16px 20px; border-top: 1px solid var(--border-subtle);">
      <a href="<?= url('/admin/logout') ?>" style="color: var(--color-error); display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 0.875rem; font-weight: 500;">
        <i data-lucide="log-out" size="18"></i> Sair do Sistema
      </a>
    </div>
  </aside>

  <!-- Main Area -->
  <div class="admin-main">
    <header class="admin-topbar">
      <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($pageTitle ?? 'Visão Geral') ?></h3>
      
      <div style="display: flex; align-items: center; gap: 16px;">
        <!-- Theme Toggle Button -->
        <button class="theme-toggle" id="themeToggle" aria-label="Alternar Tema Claro / Escuro" title="Alternar Tema Claro / Escuro" style="background: var(--bg-surface-container); border: 1px solid var(--border-subtle); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: var(--radius-sm); transition: all var(--transition-fast);">
          <i id="themeIcon" data-lucide="moon" size="18"></i>
        </button>

        <a href="<?= url('/admin/profile') ?>" style="font-size: 0.875rem; color: var(--text-secondary); text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--bg-surface-container); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
          <i data-lucide="user" size="16" style="color: var(--color-primary);"></i> <span><?= htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? 'Administrador')) ?></span>
        </a>
        
        <a href="<?= url('/admin/logout') ?>" class="btn-admin btn-secondary btn-sm" style="color: var(--color-error) !important; border-color: var(--color-error-border);">
          <i data-lucide="log-out" size="15"></i> Sair
        </a>
      </div>
    </header>

    <main class="admin-content">
      <?php if ($msg = flash('success')): ?>
        <div style="background: var(--color-success-bg); border: 1px solid var(--color-success-border); color: var(--color-success); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500;">
          <i data-lucide="check-circle" size="20"></i> <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <?php if ($err = flash('error')): ?>
        <div style="background: var(--color-error-bg); border: 1px solid var(--color-error-border); color: var(--color-error); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500;">
          <i data-lucide="alert-triangle" size="20"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>

  <!-- MODAL GLOBAL DE CONFIRMAÇÃO DE AÇÕES E EXCLUSÕES -->
  <div id="globalConfirmModal" style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.2s ease;">
    <div id="globalConfirmCard" style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); width: 90%; max-width: 440px; padding: 28px; box-shadow: var(--elevation-3); transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center;">
      
      <div id="modalIconContainer" style="width: 52px; height: 52px; border-radius: 50%; background: var(--color-error-bg); color: var(--color-error); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <i id="modalIcon" data-lucide="trash-2" size="24"></i>
      </div>

      <h3 id="modalTitle" style="color: var(--text-main); font-size: 1.15rem; font-weight: 600; margin-bottom: 8px;">
        Confirmar Exclusão
      </h3>

      <p id="modalMessage" style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.5; margin-bottom: 24px;">
        Tem certeza que deseja realizar esta ação?
      </p>

      <form id="modalActionForm" method="POST" action="">
        <?= csrf_field() ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <button type="button" onclick="closeConfirmModal()" class="btn-admin btn-secondary" style="justify-content: center; padding: 10px; font-size: 0.875rem;">
            Cancelar
          </button>
          
          <button type="submit" id="modalConfirmBtn" class="btn-admin btn-danger" style="justify-content: center; padding: 10px; font-size: 0.875rem;">
            Sim, Excluir
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Theme Switcher for Admin
    const themeBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    function updateAdminThemeIcon() {
      const curTheme = document.documentElement.getAttribute('data-theme') || 'light';
      if (themeBtn) {
        // In Light mode, show moon to switch to Dark; in Dark mode, show sun to switch to Light
        themeBtn.innerHTML = curTheme === 'dark' ? '<i data-lucide="sun" size="18"></i>' : '<i data-lucide="moon" size="18"></i>';
        themeBtn.setAttribute('title', curTheme === 'dark' ? 'Alternar para Modo Claro' : 'Alternar para Modo Escuro');
        lucide.createIcons();
      }
    }
    updateAdminThemeIcon();

    if (themeBtn) {
      themeBtn.addEventListener('click', () => {
        const curTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = curTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('admin_theme', newTheme);
        updateAdminThemeIcon();
      });
    }

    // Modal Global de Confirmação
    function openConfirmModal(options) {
      const modal = document.getElementById('globalConfirmModal');
      const card = document.getElementById('globalConfirmCard');
      const title = document.getElementById('modalTitle');
      const message = document.getElementById('modalMessage');
      const form = document.getElementById('modalActionForm');
      const confirmBtn = document.getElementById('modalConfirmBtn');
      const iconContainer = document.getElementById('modalIconContainer');

      title.innerHTML = options.title || 'Confirmar Ação';
      message.innerHTML = options.message || 'Tem certeza que deseja continuar com esta operação?';
      form.action = options.actionUrl || '';
      
      confirmBtn.innerHTML = options.btnText || '<i data-lucide="trash-2" size="15"></i> Sim, Excluir';

      if (options.type === 'warning') {
        iconContainer.style.background = 'var(--color-warning-bg)';
        iconContainer.style.color = 'var(--color-warning)';
        confirmBtn.className = 'btn-admin';
        confirmBtn.style.background = 'var(--color-warning)';
        confirmBtn.style.color = '#fff';
      } else if (options.type === 'primary') {
        iconContainer.style.background = 'var(--color-primary-container)';
        iconContainer.style.color = 'var(--color-primary)';
        confirmBtn.className = 'btn-admin btn-primary';
      } else {
        // Danger padrão
        iconContainer.style.background = 'var(--color-error-bg)';
        iconContainer.style.color = 'var(--color-error)';
        confirmBtn.className = 'btn-admin btn-danger';
      }

      modal.style.display = 'flex';
      setTimeout(() => {
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
      }, 10);

      lucide.createIcons();
    }

    function closeConfirmModal() {
      const modal = document.getElementById('globalConfirmModal');
      const card = document.getElementById('globalConfirmCard');
      if (!modal) return;
      modal.style.opacity = '0';
      card.style.transform = 'scale(0.95)';
      setTimeout(() => {
        modal.style.display = 'none';
      }, 200);
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeConfirmModal();
    });

    document.getElementById('globalConfirmModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'globalConfirmModal') closeConfirmModal();
    });

    lucide.createIcons();
  </script>
</body>
</html>
