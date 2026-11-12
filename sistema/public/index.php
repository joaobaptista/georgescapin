<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middlewares\CorsMiddleware;
use Dotenv\Dotenv;

// Erros nunca são exibidos ao cliente: detalhes internos (SQL, caminhos,
// stack trace) vão apenas para o log do servidor. APP_DEBUG=true inclui a
// mensagem da exceção na resposta, somente para desenvolvimento local.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function nuvaDebugEnabled(): bool
{
    return filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL);
}

set_exception_handler(static function (Throwable $error): void {
    error_log(sprintf(
        '[nuva] %s: %s em %s:%d',
        $error::class,
        $error->getMessage(),
        $error->getFile(),
        $error->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $payload = ['message' => 'Erro interno do servidor. Tente novamente em instantes.'];
    if (nuvaDebugEnabled()) {
        $payload['error'] = $error->getMessage();
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

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
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; connect-src 'self'; font-src 'self' data: https://fonts.gstatic.com https://unpkg.com https://cdn.jsdelivr.net; frame-ancestors 'self'; img-src 'self' data: https:; object-src 'none'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com https://cdn.jsdelivr.net");

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
