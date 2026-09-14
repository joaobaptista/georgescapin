<?php

// Ponto de entrada do Painel Administrativo na raiz do projeto
// Redireciona e processa através do Front Controller do sistema

$uri = $_SERVER['REQUEST_URI'] ?? '/admin';
$path = parse_url($uri, PHP_URL_PATH);

// Normaliza acessos diretos a /admin ou /admin/index.php para /admin
if (in_array($path, ['/admin', '/admin/', '/admin/index.php'], true)) {
    $_SERVER['REQUEST_URI'] = '/admin';
}

require_once __DIR__ . '/../public/index.php';
