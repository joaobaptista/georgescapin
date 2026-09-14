<?php

return [
    'GET' => [
        // Site Público
        '/' => ['SiteController', 'index'],
        '/index.html' => ['SiteController', 'index'],
        '/clinica' => ['SiteController', 'clinic'],
        '/clinica.html' => ['SiteController', 'clinic'],
        '/george-scapin' => ['SiteController', 'clinic'],
        '/procedimentos' => ['SiteController', 'procedures'],
        '/procedimentos.html' => ['SiteController', 'procedures'],
        '/tratamentos' => ['SiteController', 'procedures'],
        '/tratamentos.html' => ['SiteController', 'procedures'],
        '/harmonizacao-facial' => ['SiteController', 'harmonization'],
        '/harmonizacao-facial.html' => ['SiteController', 'harmonization'],
        '/contato' => ['SiteController', 'contact'],
        '/contato.html' => ['SiteController', 'contact'],
        '/blog' => ['SiteController', 'blog'],
        '/blog.html' => ['SiteController', 'blog'],
        '/blog/(?P<slug>[\w\-]+)' => ['SiteController', 'blogPost'],
        '/post-(?P<slug>[\w\-]+).html' => ['SiteController', 'blogPost'],
        '/p/(?P<slug>[\w\-]+)' => ['SiteController', 'customPage'],
        '/captcha/refresh' => ['SiteController', 'refreshCaptcha'],
        '/sitemap.xml' => ['SiteController', 'sitemap'],
        '/sitemap' => ['SiteController', 'sitemap'],
        '/robots.txt' => ['SiteController', 'robots'],

        // Auth Admin
        '/admin/login' => ['AuthController', 'loginForm'],
        '/admin/logout' => ['AuthController', 'logout'],

        // CMS Admin
        '/admin' => ['AdminController', 'dashboard'],
        '/admin/dashboard' => ['AdminController', 'dashboard'],
        
        // CMS - Procedimentos
        '/admin/procedures' => ['AdminController', 'procedures'],
        '/admin/procedures/create' => ['AdminController', 'procedureCreate'],
        '/admin/procedures/edit/(?P<id>\d+)' => ['AdminController', 'procedureEdit'],

        // CMS - Blog Posts
        '/admin/posts' => ['AdminController', 'posts'],
        '/admin/posts/create' => ['AdminController', 'postCreate'],
        '/admin/posts/edit/(?P<id>\d+)' => ['AdminController', 'postEdit'],

        // CMS - Páginas do Site & Custom Pages
        '/admin/pages' => ['AdminController', 'pages'],
        '/admin/pages/home' => ['AdminController', 'pageHome'],
        '/admin/pages/clinic' => ['AdminController', 'pageClinic'],
        '/admin/pages/harmonization' => ['AdminController', 'pageHarmonization'],
        '/admin/custom-pages' => ['AdminController', 'customPages'],
        '/admin/custom-pages/create' => ['AdminController', 'customPageCreate'],
        '/admin/custom-pages/edit/(?P<id>\d+)' => ['AdminController', 'customPageEdit'],

        // CMS - Menu de Navegação
        '/admin/menu' => ['AdminController', 'menu'],

        // CMS - Design System & UI Guide
        '/admin/design-system' => ['AdminController', 'designSystem'],

        // CMS - Leads, Newsletter, Configurações e Perfil
        '/admin/leads' => ['AdminController', 'leads'],
        '/admin/leads/export' => ['AdminController', 'exportLeadsCsv'],
        '/admin/newsletter/export' => ['AdminController', 'exportNewsletterCsv'],
        '/admin/settings' => ['AdminController', 'settings'],
        '/admin/profile' => ['AdminController', 'profile'],
    ],
    'POST' => [
        // Site Público
        '/contato/enviar' => ['SiteController', 'submitContact'],
        '/newsletter/assinar' => ['SiteController', 'submitNewsletter'],
        '/backend/api.php' => ['SiteController', 'submitContact'],

        // Auth Admin
        '/admin/login' => ['AuthController', 'login'],

        // CMS - Procedimentos
        '/admin/procedures/store' => ['AdminController', 'procedureStore'],
        '/admin/procedures/update/(?P<id>\d+)' => ['AdminController', 'procedureUpdate'],
        '/admin/procedures/delete/(?P<id>\d+)' => ['AdminController', 'procedureDelete'],
        '/admin/procedures/remove-image/(?P<id>\d+)' => ['AdminController', 'procedureRemoveImage'],

        // CMS - Blog Posts
        '/admin/posts/store' => ['AdminController', 'postStore'],
        '/admin/posts/update/(?P<id>\d+)' => ['AdminController', 'postUpdate'],
        '/admin/posts/delete/(?P<id>\d+)' => ['AdminController', 'postDelete'],
        '/admin/posts/remove-image/(?P<id>\d+)' => ['AdminController', 'postRemoveImage'],

        // CMS - Upload de Mídia para o Editor Rich Text
        '/admin/media/upload' => ['AdminController', 'uploadMedia'],

        // CMS - Páginas do Site
        '/admin/pages/home' => ['AdminController', 'updatePageHome'],
        '/admin/pages/clinic' => ['AdminController', 'updatePageClinic'],
        '/admin/pages/harmonization' => ['AdminController', 'updatePageHarmonization'],
        '/admin/pages/delete/(?P<id>[\w\-]+)' => ['AdminController', 'deletePage'],
        '/admin/pages/toggle-status/(?P<id>[\w\-]+)' => ['AdminController', 'togglePageStatus'],
        '/admin/custom-pages/store' => ['AdminController', 'customPageStore'],
        '/admin/custom-pages/update/(?P<id>\d+)' => ['AdminController', 'customPageUpdate'],
        '/admin/custom-pages/delete/(?P<id>\d+)' => ['AdminController', 'customPageDelete'],

        // CMS - Menu de Navegação
        '/admin/menu/store' => ['AdminController', 'menuStore'],
        '/admin/menu/update/(?P<id>\d+)' => ['AdminController', 'menuUpdate'],
        '/admin/menu/delete/(?P<id>\d+)' => ['AdminController', 'menuDelete'],

        // CMS - Leads, Configurações e Perfil
        '/admin/leads/status/(?P<id>\d+)' => ['AdminController', 'updateLeadStatus'],
        '/admin/settings/update' => ['AdminController', 'updateSettings'],
        '/admin/profile/update' => ['AdminController', 'updateProfile'],
    ]
];
