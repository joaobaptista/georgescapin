<?php

declare(strict_types=1);

/**
 * Minimal stand-in for Google's OpenID Connect endpoints, for local tests only:
 *   php -S 127.0.0.1:9900 tests/fake-google/router.php
 *
 * GET  /auth   — "consent screen": the test chooses the identity with ?as={json}
 * POST /token  — validates client secret, redirect URI and PKCE; returns an ID token
 */

$store = sys_get_temp_dir() . '/nuva-fake-google';
@mkdir($store, 0700, true);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$b64 = static fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
$json = static function (int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
};

if ($path === '/auth') {
    $identity = json_decode((string) ($_GET['as'] ?? '{}'), true) ?: [];
    $code = bin2hex(random_bytes(16));
    file_put_contents("{$store}/{$code}.json", json_encode([
        'client_id' => $_GET['client_id'] ?? '',
        'redirect_uri' => $_GET['redirect_uri'] ?? '',
        'challenge' => $_GET['code_challenge'] ?? '',
        'method' => $_GET['code_challenge_method'] ?? '',
        'nonce' => $_GET['nonce'] ?? null,
        'identity' => $identity,
    ]));
    header('Location: ' . $_GET['redirect_uri'] . '?' . http_build_query(['code' => $code, 'state' => $_GET['state'] ?? '']), true, 302);
    return;
}

if ($path === '/token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = "{$store}/" . preg_replace('/[^a-f0-9]/', '', (string) ($_POST['code'] ?? '')) . '.json';
    $grant = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    @unlink($file); // codes are single-use

    $pkceOk = $grant && $grant['method'] === 'S256' && hash_equals($grant['challenge'], $b64(hash('sha256', (string) ($_POST['code_verifier'] ?? ''), true)));
    if (!$grant || ($_POST['client_secret'] ?? '') !== 'test-secret' || ($_POST['redirect_uri'] ?? '') !== $grant['redirect_uri'] || !$pkceOk) {
        $json(400, ['error' => 'invalid_grant']);
        return;
    }

    $claims = [
        'iss' => 'https://accounts.google.com',
        'aud' => $grant['client_id'],
        'sub' => $grant['identity']['sub'] ?? 'fake-sub',
        'email' => $grant['identity']['email'] ?? 'fake@gmail.com',
        'email_verified' => $grant['identity']['verified'] ?? true,
        'name' => $grant['identity']['name'] ?? 'Pessoa Google',
        'nonce' => $grant['nonce'],
        'iat' => time(),
        'exp' => time() + 300,
    ];
    $json(200, [
        'access_token' => 'fake-access-token',
        'token_type' => 'Bearer',
        'id_token' => $b64('{"alg":"RS256","typ":"JWT"}') . '.' . $b64(json_encode($claims)) . '.' . $b64('fake-signature'),
    ]);
    return;
}

$json(404, ['error' => 'not_found']);
