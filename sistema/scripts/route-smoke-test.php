<?php

declare(strict_types=1);

/**
 * Non-destructive HTTP smoke test for the Nüva routes.
 *
 * Usage:
 *   NUVA_TEST_BASE_URL=http://127.0.0.1:8000 php scripts/route-smoke-test.php
 *
 * The script deliberately never sends a valid Bearer token or a valid mutation
 * payload. Protected endpoints must therefore stop at the authentication
 * middleware (401), and public POST endpoints must stop at validation (422).
 * It does not create, update, or delete application records.
 */

const REQUEST_TIMEOUT_SECONDS = 10;

$baseUrl = getenv('NUVA_TEST_BASE_URL');
if (!is_string($baseUrl) || trim($baseUrl) === '') {
    fwrite(STDERR, "NUVA_TEST_BASE_URL is required (for example, http://127.0.0.1:8000).\n");
    exit(2);
}

$baseUrl = rtrim(trim($baseUrl), '/');
$parsedBaseUrl = parse_url($baseUrl);
if (
    !is_array($parsedBaseUrl)
    || !isset($parsedBaseUrl['scheme'], $parsedBaseUrl['host'])
    || !in_array($parsedBaseUrl['scheme'], ['http', 'https'], true)
    || isset($parsedBaseUrl['query'], $parsedBaseUrl['fragment'])
) {
    fwrite(STDERR, "NUVA_TEST_BASE_URL must be an absolute http(s) URL without query parameters or fragments.\n");
    exit(2);
}

/**
 * @return array{status: int, body: string, headers: list<string>, error: ?string}
 */
function request(string $baseUrl, string $method, string $path, ?string $body = null, array $headers = []): array
{
    $requestHeaders = array_merge([
        'Connection: close',
        'User-Agent: nuva-route-smoke-test/1.0',
    ], $headers);

    $httpOptions = [
        'method' => $method,
        'header' => implode("\r\n", $requestHeaders),
        'ignore_errors' => true,
        'timeout' => REQUEST_TIMEOUT_SECONDS,
        'follow_location' => 0,
        'protocol_version' => 1.1,
    ];

    if ($body !== null) {
        $httpOptions['content'] = $body;
    }

    $context = stream_context_create(['http' => $httpOptions]);
    $warning = null;
    set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
        $warning = $message;
        return true;
    });

    $responseBody = file_get_contents($baseUrl . $path, false, $context);
    restore_error_handler();

    /** @var list<string> $http_response_header */
    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches) === 1) {
            $status = (int) $matches[1];
        }
    }

    return [
        'status' => $status,
        'body' => $responseBody === false ? '' : $responseBody,
        'headers' => $responseHeaders,
        'error' => $responseBody === false ? ($warning ?? 'HTTP request failed.') : null,
    ];
}

/** @param list<string> $headers */
function hasHeader(array $headers, string $name, ?string $contains = null): bool
{
    foreach ($headers as $header) {
        if (stripos($header, $name . ':') !== 0) {
            continue;
        }

        return $contains === null || stripos($header, $contains) !== false;
    }

    return false;
}

/** @param array{status: int, body: string, headers: list<string>, error: ?string} $response */
function isJsonResponse(array $response): bool
{
    if (!hasHeader($response['headers'], 'Content-Type', 'application/json')) {
        return false;
    }

    json_decode($response['body'], true);
    return json_last_error() === JSON_ERROR_NONE;
}

/** @param array{status: int, body: string, headers: list<string>, error: ?string} $response */
function isSpaResponse(array $response): bool
{
    return hasHeader($response['headers'], 'Content-Type', 'text/html')
        && str_contains($response['body'], 'id="app"')
        && str_contains($response['body'], '/assets/js/app.js')
        && str_contains($response['body'], '/assets/css/app.css');
}

function bodyPreview(string $body): string
{
    $normalized = trim((string) preg_replace('/\s+/', ' ', $body));
    if ($normalized === '') {
        return '(empty body)';
    }

    return mb_strimwidth($normalized, 0, 160, '…');
}

function report(string $name, bool $passed, string $detail): bool
{
    printf("%s %s — %s\n", $passed ? 'PASS' : 'FAIL', $name, $detail);
    return $passed;
}

/**
 * @param array{status: int, body: string, headers: list<string>, error: ?string} $response
 * @param list<int> $expectedStatuses
 */
function checkJson(string $name, array $response, array $expectedStatuses): bool
{
    $statusMatches = in_array($response['status'], $expectedStatuses, true);
    $jsonMatches = isJsonResponse($response);
    $passed = $statusMatches && $jsonMatches && $response['error'] === null;

    $detail = sprintf(
        'HTTP %d; expected %s; JSON %s%s',
        $response['status'],
        implode(' or ', $expectedStatuses),
        $jsonMatches ? 'yes' : 'no',
        $passed ? '' : '; ' . ($response['error'] ?? bodyPreview($response['body']))
    );

    return report($name, $passed, $detail);
}

/** @param array{status: int, body: string, headers: list<string>, error: ?string} $response */
function checkSpa(string $name, array $response): bool
{
    $spaMatches = isSpaResponse($response);
    $passed = $response['status'] === 200 && $spaMatches && $response['error'] === null;

    $detail = sprintf(
        'HTTP %d; expected 200 HTML SPA shell%s',
        $response['status'],
        $passed ? '' : '; ' . ($response['error'] ?? bodyPreview($response['body']))
    );

    return report($name, $passed, $detail);
}

$passed = 0;
$failed = 0;

function record(bool $result): void
{
    global $passed, $failed;
    if ($result) {
        $passed++;
        return;
    }

    $failed++;
}

echo "Nüva non-destructive route smoke test\n";
echo "Base URL: {$baseUrl}\n\n";

// Every browser route is requested directly to validate the PHP fallback and the
// native HTML/CSS/JavaScript application shell.
$spaRoutes = [
    '/',
    '/login',
    '/cadastro',
    '/recuperar-senha',
    '/ativar-conta',
    '/paciente',
    '/paciente/dashboard',
    '/paciente/busca',
    '/paciente/prontuario',
    '/paciente/perfil',
    '/pro',
    '/pro/procedimentos',
    '/pro/pacientes',
    '/pro/pacientes/prontuario/999999999',
    '/configuracoes',
];

foreach ($spaRoutes as $path) {
    record(checkSpa("SPA GET {$path}", request($baseUrl, 'GET', $path, null, ['Accept: text/html'])));
}

$nativeShell = request($baseUrl, 'GET', '/', null, ['Accept: text/html']);
$nativeShellPassed = isSpaResponse($nativeShell)
    && preg_match('/(?:localhost:5173|@vite|resources\/js\/app\.js)/i', $nativeShell['body']) !== 1;
record(report(
    'Native frontend shell has no build-runtime dependency',
    $nativeShellPassed,
    $nativeShellPassed
        ? 'static HTML points only to /assets/css/app.css and /assets/js/app.js'
        : 'legacy build-runtime reference found: ' . bodyPreview($nativeShell['body'])
));

$staticAssets = [
    ['/assets/css/app.css', 'text/css'],
    ['/assets/js/app.js', 'javascript'],
];
foreach ($staticAssets as [$path, $contentType]) {
    $asset = request($baseUrl, 'GET', $path);
    $assetPassed = $asset['status'] === 200
        && hasHeader($asset['headers'], 'Content-Type', $contentType)
        && $asset['body'] !== ''
        && $asset['error'] === null;
    record(report(
        "Static asset GET {$path}",
        $assetPassed,
        $assetPassed
            ? "HTTP 200; Content-Type includes {$contentType}"
            : 'HTTP ' . $asset['status'] . '; ' . ($asset['error'] ?? bodyPreview($asset['body']))
    ));
}

record(checkSpa(
    'Non-API prefix GET /apiary stays in the browser router',
    request($baseUrl, 'GET', '/apiary', null, ['Accept: text/html'])
));

// Router and CORS behavior outside the route table.
$corsOrigin = getenv('NUVA_TEST_CORS_ORIGIN') ?: 'http://127.0.0.1:8000';
$preflight = request($baseUrl, 'OPTIONS', '/api/login', null, [
    'Origin: ' . $corsOrigin,
    'Access-Control-Request-Method: POST',
    'Access-Control-Request-Headers: Content-Type, Authorization',
]);
$preflightPassed = $preflight['status'] === 204
    && hasHeader($preflight['headers'], 'Access-Control-Allow-Origin', $corsOrigin)
    && hasHeader($preflight['headers'], 'Access-Control-Allow-Methods', 'POST')
    && $preflight['error'] === null;
record(report(
    'CORS OPTIONS /api/login',
    $preflightPassed,
    sprintf(
        'HTTP %d; expected 204 with CORS headers%s',
        $preflight['status'],
        $preflightPassed ? '' : '; ' . ($preflight['error'] ?? bodyPreview($preflight['body']))
    )
));

record(checkJson('API 404 missing path', request($baseUrl, 'GET', '/api/__nuva_route_smoke_missing__', null, ['Accept: application/json']), [404]));
record(checkJson('API 405 wrong method GET /api/login', request($baseUrl, 'GET', '/api/login', null, ['Accept: application/json']), [405]));

// Public routes: intentionally invalid JSON payloads must be rejected before any write.
$invalidJsonPayload = '{}';
$publicRoutes = [
    ['POST', '/api/register', 'Public POST /api/register validation'],
    ['POST', '/api/login', 'Public POST /api/login validation'],
    ['POST', '/api/activate-account', 'Public POST /api/activate-account validation'],
];

foreach ($publicRoutes as [$method, $path, $name]) {
    record(checkJson(
        $name,
        request($baseUrl, $method, $path, $invalidJsonPayload, ['Accept: application/json', 'Content-Type: application/json']),
        [422]
    ));
}

record(checkJson(
    'Public GET /api/professionals/search',
    request($baseUrl, 'GET', '/api/professionals/search?q=x', null, ['Accept: application/json']),
    [200]
));

// Each protected route is invoked with its declared HTTP method but without a
// Bearer token. The auth middleware must return JSON 401 before any controller
// or database mutation can run.
$protectedRoutes = [
    ['GET', '/api/user'],
    ['PUT', '/api/user/profile'],
    ['PUT', '/api/user/password'],
    ['POST', '/api/logout'],
    ['GET', '/api/appointments'],
    ['POST', '/api/appointments'],
    ['PUT', '/api/appointments/999999999'],
    ['PATCH', '/api/appointments/999999999/status'],
    ['DELETE', '/api/appointments/999999999'],
    ['GET', '/api/patient/appointments'],
    ['GET', '/api/patient/medical-records'],
    ['GET', '/api/professional/dashboard'],
    ['GET', '/api/procedures'],
    ['POST', '/api/procedures'],
    ['GET', '/api/procedures/999999999'],
    ['PUT', '/api/procedures/999999999'],
    ['DELETE', '/api/procedures/999999999'],
    ['GET', '/api/patients/search?q=route-smoke'],
    ['GET', '/api/patients'],
    ['POST', '/api/patients'],
    ['GET', '/api/patients/999999999'],
    ['GET', '/api/patients/999999999/records'],
    ['POST', '/api/patients/999999999/records'],
];

foreach ($protectedRoutes as [$method, $path]) {
    $body = in_array($method, ['POST', 'PUT', 'PATCH'], true) ? '{}' : null;
    $headers = ['Accept: application/json'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    record(checkJson(
        "Protected {$method} {$path} without Bearer token",
        request($baseUrl, $method, $path, $body, $headers),
        [401]
    ));
}

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}

echo ".\n";
exit(0);
