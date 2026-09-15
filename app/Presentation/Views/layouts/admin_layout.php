<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'Painel Administrativo') ?> - Dr. George Scapin CMS</title>
  
  <!-- Favicons -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon-admin.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon-admin.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicon-admin.png') ?>">

  <!-- Google Fonts: Roboto -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  
  <!-- Admin Design System (Revista Hostil Material & Pastel Theme) -->
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
  <script>
    if (localStorage.getItem('admin_sidebar_collapsed') === 'true') {
      document.body.classList.add('sidebar-collapsed');
    }
  </script>

  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="sidebar-logo">
        <a href="<?= url('/admin') ?>" class="logo-brand-container">
          <img src="<?= asset('assets/images/stackbox-icon.svg') ?>" alt="StackBOX" class="logo-brand-icon">
          <span class="logo-brand-text">Stack<strong>BOX</strong> <span class="logo-brand-sub">CMS</span></span>
        </a>
      </div>
      
      <ul class="sidebar-nav">
        <li>
          <a href="<?= url('/admin') ?>" class="<?= ($activeTab ?? '') === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
            <i data-lucide="layout-dashboard" size="18"></i> <span class="nav-text">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/leads') ?>" class="<?= ($activeTab ?? '') === 'leads' ? 'active' : '' ?>" title="Leads & Contatos">
            <i data-lucide="users" size="18"></i> <span class="nav-text">Leads & Contatos</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/procedures') ?>" class="<?= ($activeTab ?? '') === 'procedures' ? 'active' : '' ?>" title="Procedimentos">
            <i data-lucide="sparkles" size="18"></i> <span class="nav-text">Procedimentos</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/pages') ?>" class="<?= ($activeTab ?? '') === 'pages' ? 'active' : '' ?>" title="Páginas do Site">
            <i data-lucide="layout-template" size="18"></i> <span class="nav-text">Páginas do Site</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/posts') ?>" class="<?= ($activeTab ?? '') === 'posts' ? 'active' : '' ?>" title="Artigos do Blog">
            <i data-lucide="newspaper" size="18"></i> <span class="nav-text">Artigos do Blog</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/menu') ?>" class="<?= ($activeTab ?? '') === 'menu' ? 'active' : '' ?>" title="Menu de Navegação">
            <i data-lucide="menu" size="18"></i> <span class="nav-text">Menu de Navegação</span>
          </a>
        </li>
        <li>
          <a href="<?= url('/admin/settings') ?>" class="<?= ($activeTab ?? '') === 'settings' ? 'active' : '' ?>" title="Configurações & SEO">
            <i data-lucide="sliders" size="18"></i> <span class="nav-text">Configurações & SEO</span>
          </a>
        </li>
      </ul>

      <div class="sidebar-footer">
        <div class="sidebar-footer-info">
          <span class="sidebar-version">v2.5.0</span>
          <span class="sidebar-copyright">
            &copy; <?= date('Y') ?> <a href="https://stackbox.com.br/" target="_blank" rel="noopener noreferrer">Stack<strong>BOX</strong></a>
          </span>
        </div>
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Recolher / Expandir Menu" aria-label="Recolher / Expandir Menu">
          <i data-lucide="chevrons-left" size="18" class="toggle-icon-collapse"></i>
          <i data-lucide="chevrons-right" size="18" class="toggle-icon-expand"></i>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
      <?php
      $adminUserName = $user['name'] ?? ($_SESSION['user_name'] ?? 'Dr. George Scapin');
      $adminUserEmail = $user['email'] ?? ($_SESSION['user_email'] ?? 'admin@georgescapin.com.br');

      $cleanName = trim(preg_replace('/^(Dr\.|Dra\.|Dr|Dra)\s+/i', '', $adminUserName));
      $words = preg_split('/\s+/', $cleanName);
      $initials = '';
      if (count($words) >= 2) {
          $initials = mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1);
      } elseif (!empty($words[0])) {
          $initials = mb_substr($words[0], 0, 2);
      }
      $initials = strtoupper($initials ?: 'GS');
      ?>

      <header class="admin-topbar">
        <div class="topbar-left">
          <!-- Topbar limpo sem título -->
        </div>
        
        <div class="topbar-right">
          <!-- User Avatar Dropdown -->
          <div class="user-dropdown-wrapper" id="userDropdownWrapper">
            <button type="button" class="user-avatar-btn" id="userAvatarBtn" aria-expanded="false" aria-haspopup="true" title="<?= htmlspecialchars($adminUserName) ?>">
              <div class="avatar-circle">
                <?= htmlspecialchars($initials) ?>
              </div>
              <i data-lucide="chevron-down" size="14" class="avatar-chevron"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="user-dropdown-menu" id="userDropdownMenu">
              <div class="user-dropdown-header">
                <div class="user-dropdown-name"><?= htmlspecialchars($adminUserName) ?></div>
                <div class="user-dropdown-email"><?= htmlspecialchars($adminUserEmail) ?></div>
              </div>

              <div class="user-dropdown-body">
                <a href="<?= url('/admin/profile') ?>" class="user-dropdown-item">
                  <span style="display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="user" size="16"></i> Meu Perfil
                  </span>
                </a>

                <a href="<?= url('/') ?>" target="_blank" class="user-dropdown-item">
                  <span style="display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="external-link" size="16"></i> Ver Site Público
                  </span>
                </a>

                <button type="button" class="user-dropdown-item" id="themeToggleDropdown">
                  <span style="display: flex; align-items: center; gap: 10px;">
                    <i id="themeDropdownIcon" data-lucide="moon" size="16"></i>
                    <span id="themeDropdownText">Modo Escuro</span>
                  </span>
                  <span class="theme-badge-status" id="themeBadgeStatus">Claro</span>
                </button>

                <div class="user-dropdown-divider"></div>

                <a href="<?= url('/admin/logout') ?>" class="user-dropdown-item danger">
                  <span style="display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="log-out" size="16"></i> Sair
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="admin-content">
        <?php
        // Caminho de navegação (Breadcrumbs)
        if (!isset($breadcrumbs)) {
            if (($activeTab ?? '') !== 'dashboard') {
                $breadcrumbs = [
                    ['label' => 'Dashboard', 'url' => url('/admin')],
                    ['label' => $pageTitle ?? 'Página']
                ];
            } else {
                $breadcrumbs = [
                    ['label' => 'Dashboard']
                ];
            }
        }
        ?>

        <?php if (!empty($breadcrumbs) || !empty($pageActions)): ?>
          <div class="admin-page-header">
            <?php if (!empty($breadcrumbs)): ?>
              <nav class="admin-breadcrumb" aria-label="Caminho de navegação">
                <ol class="breadcrumb-list">
                  <?php foreach ($breadcrumbs as $index => $item): ?>
                    <?php $isLast = ($index === count($breadcrumbs) - 1); ?>
                    <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>">
                      <?php if (!$isLast && !empty($item['url'])): ?>
                        <a href="<?= $item['url'] ?>" class="breadcrumb-link"><?= htmlspecialchars($item['label']) ?></a>
                        <span class="breadcrumb-separator">/</span>
                      <?php else: ?>
                        <span class="breadcrumb-current"><?= htmlspecialchars($item['label']) ?></span>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ol>
              </nav>
            <?php else: ?>
              <div></div>
            <?php endif; ?>

            <?php if (!empty($pageActions)): ?>
              <div class="admin-page-actions">
                <?= $pageActions ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($msg = flash('success')): ?>
          <div style="background: var(--pastel-green-bg); border: 1px solid var(--pastel-green-border); color: var(--pastel-green-text); padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500; box-shadow: var(--shadow-xs);">
            <i data-lucide="check-circle" size="20"></i> <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>

        <?php if ($err = flash('error')): ?>
          <div style="background: var(--pastel-red-bg); border: 1px solid var(--pastel-red-border); color: var(--pastel-red-text); padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 500; box-shadow: var(--shadow-xs);">
            <i data-lucide="alert-triangle" size="20"></i> <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <?= $content ?>
      </main>
    </div>
  </div>

  <!-- MODAL GLOBAL DE CONFIRMAÇÃO DE AÇÕES E EXCLUSÕES -->
  <div id="globalConfirmModal" style="display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.2s ease;">
    <div id="globalConfirmCard" style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); width: 90%; max-width: 440px; padding: 28px; box-shadow: var(--shadow-lg); transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center;">
      
      <div id="modalIconContainer" style="width: 52px; height: 52px; border-radius: 50%; background: var(--pastel-red-bg); color: var(--pastel-red-accent); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <i id="modalIcon" data-lucide="trash-2" size="24"></i>
      </div>

      <h3 id="modalTitle" style="color: var(--text-primary); font-size: 1.18rem; font-weight: 600; margin-bottom: 8px;">
        Confirmar Exclusão
      </h3>

      <p id="modalMessage" style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.5; margin-bottom: 24px;">
        Tem certeza que deseja realizar esta ação?
      </p>

      <form id="modalActionForm" method="POST" action="">
        <?= csrf_field() ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <button type="button" onclick="closeConfirmModal()" class="btn-admin btn-secondary" style="justify-content: center; padding: 10px; font-size: 0.88rem;">
            Cancelar
          </button>
          
          <button type="submit" id="modalConfirmBtn" class="btn-admin btn-danger" style="justify-content: center; padding: 10px; font-size: 0.88rem;">
            Sim, Excluir
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Avatar Dropdown Toggle
    const avatarBtn = document.getElementById('userAvatarBtn');
    const dropdownMenu = document.getElementById('userDropdownMenu');

    if (avatarBtn && dropdownMenu) {
      avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isExpanded = avatarBtn.getAttribute('aria-expanded') === 'true';
        avatarBtn.setAttribute('aria-expanded', !isExpanded);
        dropdownMenu.classList.toggle('show');
      });

      // Fechar ao clicar fora
      document.addEventListener('click', (e) => {
        if (!dropdownMenu.contains(e.target) && !avatarBtn.contains(e.target)) {
          dropdownMenu.classList.remove('show');
          avatarBtn.setAttribute('aria-expanded', 'false');
        }
      });

      // Fechar com ESC
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          dropdownMenu.classList.remove('show');
          avatarBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // Theme Switcher for Admin (Inside Dropdown)
    const themeDropdownBtn = document.getElementById('themeToggleDropdown');
    const themeDropdownIcon = document.getElementById('themeDropdownIcon');
    const themeDropdownText = document.getElementById('themeDropdownText');
    const themeBadgeStatus = document.getElementById('themeBadgeStatus');

    function updateAdminThemeUI() {
      const curTheme = document.documentElement.getAttribute('data-theme') || 'light';
      if (themeDropdownIcon && themeDropdownText && themeBadgeStatus) {
        if (curTheme === 'dark') {
          themeDropdownIcon.setAttribute('data-lucide', 'sun');
          themeDropdownText.textContent = 'Modo Claro';
          themeBadgeStatus.textContent = 'Escuro';
          themeBadgeStatus.style.background = 'var(--pastel-amber-bg)';
          themeBadgeStatus.style.color = 'var(--pastel-amber-text)';
          themeBadgeStatus.style.borderColor = 'var(--pastel-amber-border)';
        } else {
          themeDropdownIcon.setAttribute('data-lucide', 'moon');
          themeDropdownText.textContent = 'Modo Escuro';
          themeBadgeStatus.textContent = 'Claro';
          themeBadgeStatus.style.background = 'var(--bg-surface-hover)';
          themeBadgeStatus.style.color = 'var(--text-muted)';
          themeBadgeStatus.style.borderColor = 'var(--border-subtle)';
        }
        lucide.createIcons();
      }
    }
    updateAdminThemeUI();

    if (themeDropdownBtn) {
      themeDropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const curTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = curTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('admin_theme', newTheme);
        updateAdminThemeUI();
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
        iconContainer.style.background = 'var(--pastel-amber-bg)';
        iconContainer.style.color = 'var(--pastel-amber-accent)';
        confirmBtn.className = 'btn-admin';
        confirmBtn.style.background = 'var(--pastel-amber-accent)';
        confirmBtn.style.color = '#fff';
      } else if (options.type === 'primary') {
        iconContainer.style.background = 'var(--pastel-blue-bg)';
        iconContainer.style.color = 'var(--pastel-blue-accent)';
        confirmBtn.className = 'btn-admin btn-primary';
      } else {
        // Danger padrão
        iconContainer.style.background = 'var(--pastel-red-bg)';
        iconContainer.style.color = 'var(--pastel-red-accent)';
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
      if (e.key === 'Escape') {
        closeConfirmModal();
        document.querySelectorAll('.table-action-dropdown.active').forEach(d => d.classList.remove('active'));
      }
    });

    document.getElementById('globalConfirmModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'globalConfirmModal') closeConfirmModal();
    });

    // Dropdown de Ações das Tabelas (Menu 3 Pontinhos)
    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('.table-action-trigger');
      const allDropdowns = document.querySelectorAll('.table-action-dropdown.active');

      if (trigger) {
        e.preventDefault();
        e.stopPropagation();
        const currentDropdown = trigger.closest('.table-action-dropdown');
        allDropdowns.forEach(d => {
          if (d !== currentDropdown) d.classList.remove('active');
        });
        currentDropdown.classList.toggle('active');
        if (window.lucide) lucide.createIcons();
        return;
      }

      if (e.target.closest('.table-action-item')) {
        allDropdowns.forEach(d => d.classList.remove('active'));
        return;
      }

      if (!e.target.closest('.table-action-dropdown')) {
        allDropdowns.forEach(d => d.classList.remove('active'));
      }
    });

    // Sidebar Collapse / Expand Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    if (sidebarToggleBtn) {
      sidebarToggleBtn.addEventListener('click', () => {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        if (window.lucide) {
          lucide.createIcons();
        }
      });
    }

    lucide.createIcons();
  </script>
</body>
</html>
