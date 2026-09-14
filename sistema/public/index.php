<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middlewares\CorsMiddleware;
use Dotenv\Dotenv;

// Load .env
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

// HTTP Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; connect-src 'self'; font-src 'self' data: https://fonts.gstatic.com https://unpkg.com; frame-ancestors 'self'; img-src 'self' data:; object-src 'none'; script-src 'self' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com");

$request = new Request();

// API requests stay in PHP. The visual application is a static HTML/CSS/JS
// client served below, while all data and authorization continue to use this
// native router.
$path = $request->getPath();
if ($path === '/api' || str_starts_with($path, '/api/')) {
    // CORS Headers & Preflight only apply to the API.
    (new CorsMiddleware())->handle($request);
    $router = new Router();
    require_once __DIR__ . '/../routes/api.php';
    $router->dispatch($request);
    exit;
}

if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

// Every public page is resolved by the native browser router in app.js.
// The static shell has no client-side compilation runtime.
header('Content-Type: text/html; charset=utf-8');
if ($request->getMethod() !== 'HEAD') {
    readfile(__DIR__ . '/index.html');
}
