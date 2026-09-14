<?php

// Ponto de entrada do Painel Administrativo dentro de public/admin
// Processa as requisições através do Front Controller principal

$uri = $_SERVER['REQUEST_URI'] ?? '/admin';
$path = parse_url($uri, PHP_URL_PATH);

// Normaliza acessos diretos para /admin
if (in_array($path, ['/admin', '/admin/', '/admin/index.php'], true)) {
    $_SERVER['REQUEST_URI'] = '/admin';
}

require_once __DIR__ . '/../index.php';
