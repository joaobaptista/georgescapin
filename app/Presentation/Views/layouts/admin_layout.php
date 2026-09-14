<!DOCTYPE html>
<html lang="pt-br" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> | Dr. George Scapin CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <!-- Favicon Admin -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin-design-system.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <!-- Quill Rich Text Editor -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <script>
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>

  <style>
    /* Quill Rich Text Editor Theme Customization */
    .ql-toolbar.ql-snow {
      border: 1px solid var(--card-border) !important;
      background: var(--bg-light) !important;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      padding: 10px 14px !important;
    }
    .ql-container.ql-snow {
      border: 1px solid var(--card-border) !important;
      border-top: none !important;
      background: var(--bg-input, var(--bg-color)) !important;
      color: var(--text-main) !important;
      font-family: var(--font-sans, 'Plus Jakarta Sans', sans-serif) !important;
      font-size: 1rem !important;
      border-bottom-left-radius: 8px;
      border-bottom-right-radius: 8px;
      min-height: 280px;
    }
    .ql-editor {
      min-height: 280px;
      line-height: 1.8;
      color: var(--text-main);
    }
    .ql-snow .ql-stroke {
      stroke: var(--text-muted) !important;
    }
    .ql-snow .ql-fill {
      fill: var(--text-muted) !important;
    }
    .ql-snow .ql-picker {
      color: var(--text-muted) !important;
    }
    .ql-snow.ql-toolbar button:hover, .ql-snow .ql-toolbar button:focus, .ql-snow .ql-toolbar button.ql-active {
      color: var(--gold-primary) !important;
    }
    .ql-snow.ql-toolbar button:hover .ql-stroke, .ql-snow .ql-toolbar button.ql-active .ql-stroke {
      stroke: var(--gold-primary) !important;
    }
    .ql-snow.ql-toolbar button:hover .ql-fill, .ql-snow .ql-toolbar button.ql-active .ql-fill {
      fill: var(--gold-primary) !important;
    }
    .ql-snow .ql-picker-options {
      background-color: var(--bg-light) !important;
      border: 1px solid var(--card-border) !important;
    }
    .ql-editor.ql-blank::before {
      color: var(--text-muted) !important;
      font-style: normal;
    }
    .ql-editor img {
      max-width: 100%;
      border-radius: 8px;
      margin: 15px 0;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    [data-theme="light"] .ql-toolbar.ql-snow {
      background: #f8fafc !important;
      border-color: #cbd5e1 !important;
    }
    [data-theme="light"] .ql-container.ql-snow {
      background: #ffffff !important;
      border-color: #cbd5e1 !important;
      color: #0f172a !important;
    }
    [data-theme="light"] .ql-editor {
      color: #0f172a !important;
    }
    [data-theme="light"] .ql-snow .ql-stroke {
      stroke: #64748b !important;
    }
    [data-theme="light"] .ql-snow .ql-fill {
      fill: #64748b !important;
    }
    [data-theme="light"] .ql-snow .ql-picker {
      color: #64748b !important;
    }
    :root {
      --admin-sidebar-w: 260px;
    }
    body {
      display: flex;
      min-height: 100vh;
      background: #090e0b;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    .admin-sidebar {
      width: var(--admin-sidebar-w);
      background: #060907;
      border-right: 1px solid var(--card-border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; bottom: 0; left: 0;
      z-index: 100;
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .admin-brand {
      padding: 25px 20px;
      border-bottom: 1px solid var(--card-border);
      text-align: center;
    }
    .admin-brand h2 {
      font-size: 1.2rem;
      color: var(--gold-light);
      margin-bottom: 4px;
    }
    .admin-brand span {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--gold-primary);
    }
    .admin-nav {
      flex: 1;
      padding: 20px 0;
      display: flex;
      flex-direction: column;
      gap: 5px;
      overflow-y: auto;
    }
    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 25px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    .admin-nav a:hover, .admin-nav a.active {
      color: var(--gold-light);
      background: rgba(197, 160, 89, 0.08);
      border-left: 3px solid var(--gold-primary);
    }
    .admin-main {
      flex: 1;
      margin-left: var(--admin-sidebar-w);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: #090e0b;
      transition: background-color 0.3s ease;
    }
    .admin-topbar {
      height: 70px;
      background: rgba(6, 9, 7, 0.85);
      border-bottom: 1px solid var(--card-border);
      padding: 0 35px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 90;
      backdrop-filter: blur(10px);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .admin-content {
      flex: 1;
      padding: 35px;
    }
    .admin-card {
      background: var(--bg-light);
      border: 1px solid var(--card-border);
      border-radius: 8px;
      padding: 25px;
      margin-bottom: 25px;
      transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    .admin-table th, .admin-table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid var(--card-border);
      font-size: 0.9rem;
    }
    .admin-table th {
      color: var(--gold-light);
      font-family: var(--font-serif);
      font-weight: 500;
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      text-transform: uppercase;
      font-weight: 600;
    }
    .badge-success { background: rgba(37, 211, 102, 0.15); color: #25D366; }
    .badge-warning { background: rgba(234, 179, 8, 0.15); color: #eab308; }
    .badge-muted { background: rgba(160, 160, 160, 0.15); color: #a0a0a0; }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: var(--bg-light);
      border: 1px solid var(--card-border);
      border-radius: 8px;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 20px;
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      background: rgba(197, 160, 89, 0.12);
      color: var(--gold-primary);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .stat-val {
      font-size: 1.8rem;
      color: var(--gold-light);
      font-family: var(--font-serif);
      line-height: 1.1;
    }
    .stat-label {
      font-size: 0.85rem;
      color: var(--text-muted);
    }

    /* LIGHT THEME RULES FOR ADMIN */
    [data-theme="light"] body {
      background: #f1f5f9;
      color: #1e293b;
    }
    [data-theme="light"] .admin-sidebar {
      background: #ffffff;
      border-right: 1px solid rgba(156, 122, 49, 0.2);
    }
    [data-theme="light"] .admin-brand {
      border-bottom: 1px solid rgba(156, 122, 49, 0.2);
    }
    [data-theme="light"] .admin-brand h2 {
      color: var(--gold-primary);
    }
    [data-theme="light"] .admin-nav a {
      color: #64748b;
    }
    [data-theme="light"] .admin-nav a:hover, [data-theme="light"] .admin-nav a.active {
      color: var(--gold-primary);
      background: rgba(156, 122, 49, 0.1);
      border-left: 3px solid var(--gold-primary);
    }
    [data-theme="light"] .admin-main {
      background: #f8fafc;
    }
    [data-theme="light"] .admin-topbar {
      background: rgba(255, 255, 255, 0.9);
      border-bottom: 1px solid rgba(156, 122, 49, 0.2);
    }
    [data-theme="light"] .admin-topbar h3 {
      color: #0f172a !important;
    }
    [data-theme="light"] .admin-card {
      background: #ffffff;
      border: 1px solid rgba(156, 122, 49, 0.2);
      box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    [data-theme="light"] .admin-card h3 {
      color: var(--gold-primary) !important;
    }
    [data-theme="light"] .stat-card {
      background: #ffffff;
      border: 1px solid rgba(156, 122, 49, 0.2);
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    [data-theme="light"] .stat-icon {
      background: rgba(156, 122, 49, 0.1);
    }
    [data-theme="light"] .stat-val {
      color: var(--gold-primary);
    }
    [data-theme="light"] .admin-table th {
      color: #0f172a;
      border-bottom: 2px solid rgba(156, 122, 49, 0.2);
    }
    [data-theme="light"] .admin-table td {
      border-bottom: 1px solid #e2e8f0;
      color: #334155;
    }
    [data-theme="light"] .form-control {
      background-color: #ffffff;
      border-color: #cbd5e1;
      color: #0f172a;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <h2>Dr. George Scapin</h2>
      <span>Painel CMS</span>
    </div>
    <nav class="admin-nav">
      <a href="<?= url('/admin') ?>" class="<?= ($activeTab ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard" size="18"></i> Dashboard
      </a>
      <a href="<?= url('/admin/procedures') ?>" class="<?= ($activeTab ?? '') === 'procedures' ? 'active' : '' ?>">
        <i data-lucide="sparkles" size="18"></i> Procedimentos
      </a>
      <a href="<?= url('/admin/posts') ?>" class="<?= ($activeTab ?? '') === 'posts' ? 'active' : '' ?>">
        <i data-lucide="newspaper" size="18"></i> Artigos do Blog
      </a>
      <a href="<?= url('/admin/pages') ?>" class="<?= ($activeTab ?? '') === 'pages' ? 'active' : '' ?>">
        <i data-lucide="layout-template" size="18"></i> Páginas do Site
      </a>
      <a href="<?= url('/admin/menu') ?>" class="<?= ($activeTab ?? '') === 'menu' ? 'active' : '' ?>">
        <i data-lucide="menu" size="18"></i> Menu de Navegação
      </a>
      <a href="<?= url('/admin/leads') ?>" class="<?= ($activeTab ?? '') === 'leads' ? 'active' : '' ?>">
        <i data-lucide="users" size="18"></i> Leads & Contatos
      </a>
      <a href="<?= url('/admin/settings') ?>" class="<?= ($activeTab ?? '') === 'settings' ? 'active' : '' ?>">
        <i data-lucide="sliders" size="18"></i> Conteúdo & SEO
      </a>
      <a href="<?= url('/admin/design-system') ?>" class="<?= ($activeTab ?? '') === 'design-system' ? 'active' : '' ?>">
        <i data-lucide="palette" size="18"></i> Design System
      </a>
      <a href="<?= url('/admin/profile') ?>" class="<?= ($activeTab ?? '') === 'profile' ? 'active' : '' ?>">
        <i data-lucide="shield-check" size="18"></i> Meu Perfil
      </a>
      <a href="<?= url('/') ?>" target="_blank">
        <i data-lucide="external-link" size="18"></i> Ver Site Público
      </a>
    </nav>
    <div style="padding: 20px; border-top: 1px solid var(--card-border);">
      <a href="<?= url('/admin/logout') ?>" style="color: #ef4444; display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 0.9rem;">
        <i data-lucide="log-out" size="18"></i> Sair do Sistema
      </a>
    </div>
  </aside>

  <!-- Main Area -->
  <div class="admin-main">
    <header class="admin-topbar">
      <h3 style="font-size: 1.1rem; color: var(--gold-light);"><?= htmlspecialchars($pageTitle ?? 'Visão Geral') ?></h3>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <!-- Theme Toggle Button -->
        <button class="theme-toggle" id="themeToggle" aria-label="Alternar Tema Claro / Escuro" title="Alternar Tema Claro / Escuro" style="background: rgba(197,160,89,0.1); border: 1px solid var(--card-border); color: var(--gold-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 8px; border-radius: 50%; width: 38px; height: 38px; transition: transform 0.2s, background-color 0.3s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
          <i data-lucide="sun" size="20"></i>
        </button>

        <a href="<?= url('/admin/profile') ?>" style="font-size: 0.9rem; color: var(--gold-primary); text-decoration: none; display: flex; align-items: center; gap: 6px;">
          <i data-lucide="user" size="16"></i> <strong><?= htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? 'Administrador')) ?></strong>
        </a>
        
        <a href="<?= url('/admin/logout') ?>" class="btn-primary" style="padding: 6px 15px; font-size: 0.8rem; border-color: #ef4444; color: #ef4444;">Sair</a>
      </div>
    </header>

    <main class="admin-content">
      <?php if ($msg = flash('success')): ?>
        <div style="background: rgba(37, 211, 102, 0.15); border: 1px solid #25D366; color: #25D366; padding: 14px 20px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
          <i data-lucide="check-circle" size="20"></i> <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <?php if ($err = flash('error')): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 14px 20px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
          <i data-lucide="alert-triangle" size="20"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>

  <!-- MODAL GLOBAL DE CONFIRMAÇÃO DE AÇÕES E EXCLUSÕES -->
  <div id="globalConfirmModal" style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px); opacity: 0; transition: opacity 0.2s ease;">
    <div id="globalConfirmCard" style="background: var(--bg-light); border: 1px solid var(--card-border); border-radius: 14px; width: 90%; max-width: 440px; padding: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center;">
      
      <div id="modalIconContainer" style="width: 56px; height: 56px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <i id="modalIcon" data-lucide="trash-2" size="26"></i>
      </div>

      <h3 id="modalTitle" style="color: var(--text-main); font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">
        Confirmar Exclusão
      </h3>

      <p id="modalMessage" style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 24px;">
        Tem certeza que deseja realizar esta ação?
      </p>

      <form id="modalActionForm" method="POST" action="">
        <?= csrf_field() ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <button type="button" onclick="closeConfirmModal()" class="btn-primary" style="justify-content: center; padding: 11px; font-size: 0.88rem;">
            Cancelar
          </button>
          
          <button type="submit" id="modalConfirmBtn" class="btn-primary btn-solid" style="justify-content: center; padding: 11px; font-size: 0.88rem; background: #ef4444; border-color: #ef4444; color: #fff;">
            Sim, Excluir
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Theme Switcher
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
      themeBtn.addEventListener('click', () => {
        const curTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = curTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        lucide.createIcons();
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
      const icon = document.getElementById('modalIcon');

      title.innerHTML = options.title || 'Confirmar Ação';
      message.innerHTML = options.message || 'Tem certeza que deseja continuar com esta operação?';
      form.action = options.actionUrl || '';
      
      confirmBtn.innerHTML = options.btnText || '<i data-lucide="trash-2" size="15"></i> Sim, Excluir';

      if (options.type === 'warning') {
        iconContainer.style.background = 'rgba(245, 158, 11, 0.15)';
        iconContainer.style.color = '#f59e0b';
        confirmBtn.style.background = '#f59e0b';
        confirmBtn.style.borderColor = '#f59e0b';
      } else if (options.type === 'primary') {
        iconContainer.style.background = 'rgba(197, 160, 89, 0.15)';
        iconContainer.style.color = 'var(--gold-primary)';
        confirmBtn.style.background = 'var(--gold-primary)';
        confirmBtn.style.borderColor = 'var(--gold-primary)';
      } else {
        // Danger padrão
        iconContainer.style.background = 'rgba(239, 68, 68, 0.15)';
        iconContainer.style.color = '#ef4444';
        confirmBtn.style.background = '#ef4444';
        confirmBtn.style.borderColor = '#ef4444';
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
  </script>
</body>
</html>
