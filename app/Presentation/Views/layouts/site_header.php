<!DOCTYPE html>
<html lang="pt-br" data-theme="dark">

<?php
  $siteUrl = 'https://www.drgeorgescapin.com.br';
  if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $siteUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
  }
  $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
  $canonicalUrl = rtrim($siteUrl, '/') . ($currentPath === '/' ? '/' : $currentPath);
  
  $metaTitle = $pageTitle ?? 'Dr. George Scapin | Harmonização e Estética Facial Avançada em RS';
  $metaDesc = $metaDescription ?? 'Clínica Dr. George Scapin em Porto Alegre. Especialista em Estética Facial, Toxina Botulínica, Preenchimento e Harmonização Full Face.';
  $metaKeys = $settings['meta_keywords'] ?? 'Dr. George Scapin, estética facial, harmonização facial, toxina botulínica, preenchimento facial, full face, clínica de estética Porto Alegre, rejuvenescimento';
  
  $ogImg = $siteUrl . '/assets/images/hero.png';
  if (!empty($post['image_url'])) {
      $ogImg = (strpos($post['image_url'], 'http') === 0) ? $post['image_url'] : $siteUrl . '/' . ltrim($post['image_url'], '/');
  }
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeys) ?>">
  <meta name="author" content="Dr. George Scapin">
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
  
  <!-- Geo Tags / Local SEO -->
  <meta name="geo.region" content="BR-RS">
  <meta name="geo.placename" content="Porto Alegre">
  <meta name="geo.position" content="-30.0538;-51.2291">
  <meta name="ICBM" content="-30.0538, -51.2291">
  
  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="Dr. George Scapin | Clínica de Estética Facial">
  <meta property="og:type" content="<?= isset($post) ? 'article' : 'website' ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImg) ?>">
  <meta property="og:image:alt" content="<?= htmlspecialchars($metaTitle) ?>">

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImg) ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <!-- Favicons -->
  <link rel="icon" type="image/svg+xml" href="<?= asset('favicon.svg') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicon.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('apple-touch-icon.png') ?>">

  <!-- Estilos Globais -->
  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <!-- Schema.org JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MedicalBusiness",
    "name": "Dr. George Scapin - Clínica de Harmonização e Estética Facial",
    "image": "<?= htmlspecialchars($siteUrl . '/assets/images/hero.png') ?>",
    "@id": "<?= htmlspecialchars($siteUrl) ?>",
    "url": "<?= htmlspecialchars($siteUrl) ?>",
    "telephone": "<?= htmlspecialchars($settings['contact_phone'] ?? '(51) 99824-4379') ?>",
    "priceRange": "$$",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Av. Ipiranga, 40, sala 1512 - Praia de Belas",
      "addressLocality": "Porto Alegre",
      "addressRegion": "RS",
      "postalCode": "90160-090",
      "addressCountry": "BR"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": -30.0538,
      "longitude": -51.2291
    },
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "opens": "08:00",
        "closes": "19:00"
      },
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": "Saturday",
        "opens": "09:00",
        "closes": "13:00"
      }
    ],
    "founder": {
      "@type": "Person",
      "name": "Dr. George Scapin",
      "jobTitle": "Biomédico Esteta",
      "identifier": "CRBM 5202"
    }
  }
  </script>
  <?php if (isset($post)): ?>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": "<?= htmlspecialchars($canonicalUrl) ?>"
    },
    "headline": <?= json_encode($post['title']) ?>,
    "description": <?= json_encode($post['summary'] ?? '') ?>,
    "image": <?= json_encode($ogImg) ?>,
    "author": {
      "@type": "Person",
      "name": <?= json_encode($post['author'] ?? 'Dr. George Scapin') ?>
    },
    "publisher": {
      "@type": "Organization",
      "name": "Dr. George Scapin",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= htmlspecialchars($siteUrl . '/assets/images/logo.svg') ?>"
      }
    },
    "datePublished": "<?= date('c', strtotime($post['created_at'])) ?>",
    "dateModified": "<?= date('c', strtotime($post['updated_at'] ?? $post['created_at'])) ?>"
  }
  </script>
  <?php endif; ?>

  <script>
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
</head>

<body>

  <header id="header">
    <a href="<?= url('/') ?>" class="logo" aria-label="Dr. George Scapin">
      <div class="logo-svg" style="background-image: url('<?= asset('assets/images/logo.svg') ?>');"></div>
    </a>
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir Menu">
      <i data-lucide="menu" size="28"></i>
    </button>
    <div class="nav-actions">
      <nav id="navMenu">
        <?php 
          $menuRepo = new \App\Infrastructure\Repositories\PDOMenuRepository();
          $menuItems = $menuRepo->getAll(true);
          $reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        ?>
        <?php foreach ($menuItems as $m): 
          $isCur = ($reqUri === $m['url']) || ($m['url'] === '/' && ($reqUri === '' || $reqUri === '/'));
          $isBtn = !empty($m['is_button']);
        ?>
          <a href="<?= (strpos($m['url'], 'http') === 0) ? $m['url'] : url($m['url']) ?>" 
             target="<?= htmlspecialchars($m['target'] ?? '_self') ?>"
             class="<?= $isCur ? 'active' : '' ?> <?= $isBtn ? 'btn-primary btn-solid' : '' ?>"
             <?= $isBtn ? 'style="padding: 8px 18px; margin-left: 10px;"' : '' ?>>
            <?= htmlspecialchars($m['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <button class="theme-toggle" id="themeToggle" aria-label="Alternar Tema">
        <i data-lucide="sun" size="24"></i>
      </button>
    </div>
  </header>
