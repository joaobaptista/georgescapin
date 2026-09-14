<?php

// Suporte para o servidor embutido do PHP (php -S)
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($filePath)) {
        return false; // Serve o arquivo estático diretamente
    }
}

// Configuração segura de cookies de sessão
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Headers Globais de Segurança HTTP
@header_remove('X-Powered-By');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

if ($isSecure) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Autoloader PSR-4 para o namespace App\
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../app/helpers.php';

$routes = require __DIR__ . '/../config/routes.php';

// Resolução da URI e Método
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = '/' . trim($request_uri, '/');
if ($request_uri === '//') $request_uri = '/';

$request_method = ($_SERVER['REQUEST_METHOD'] === 'HEAD') ? 'GET' : $_SERVER['REQUEST_METHOD'];

function resolveController(string $controllerClass) {
    return new $controllerClass();
}

$matched = false;

// 1. Verificação de Rota Estática
if (isset($routes[$request_method][$request_uri])) {
    $handler = $routes[$request_method][$request_uri];
    $controllerName = 'App\\Presentation\\Controllers\\' . $handler[0];
    $action = $handler[1];
    
    $controller = resolveController($controllerName);
    $controller->$action();
    $matched = true;
} else {
    // 2. Verificação de Rota Dinâmica com Expressões Regulares
    if (isset($routes[$request_method])) {
        foreach ($routes[$request_method] as $pattern => $handler) {
            if (strpos($pattern, '(') !== false) {
                if (preg_match('#^' . $pattern . '$#', $request_uri, $matches)) {
                    $controllerName = 'App\\Presentation\\Controllers\\' . $handler[0];
                    $action = $handler[1];
                    $controller = resolveController($controllerName);
                    
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    call_user_func_array([$controller, $action], array_values($params));
                    $matched = true;
                    break;
                }
            }
        }
    }
}

if (!$matched) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-br" data-theme="dark"><head><meta charset="UTF-8"><title>404 - Página Não Encontrada | Dr. George Scapin</title><link rel="stylesheet" href="/assets/css/style.css"></head><body style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;text-align:center;padding:20px;"><div class="logo-svg" style="background-image:url(\'/assets/images/logo.svg\');background-position:center;width:240px;height:50px;margin-bottom:30px;"></div><h1 style="color:var(--gold-light);font-size:2.5rem;margin-bottom:10px;">404</h1><p style="color:var(--text-muted);margin-bottom:30px;max-width:500px;">A página que você está procurando não existe ou foi movida.</p><a href="/" class="btn-primary">Voltar ao Início</a></body></html>';
}
