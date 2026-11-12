<?php

declare(strict_types=1);

/**
 * End-to-end test of "Entrar com o Google" over real HTTP, against the fake
 * provider in tests/fake-google. Never touches Google or a real database.
 *
 *   php -S 127.0.0.1:9900 tests/fake-google/router.php &
 *   GOOGLE_CLIENT_ID=test-client GOOGLE_CLIENT_SECRET=test-secret \
 *   GOOGLE_AUTH_ENDPOINT=http://127.0.0.1:9900/auth GOOGLE_TOKEN_ENDPOINT=http://127.0.0.1:9900/token \
 *   APP_URL=http://127.0.0.1:8000 DB_CONNECTION=sqlite DB_DATABASE="$TEST_DB" \
 *   php -d variables_order=EGPCS -S 127.0.0.1:8000 -t public &
 *   NUVA_GOOGLE_E2E_BASE_URL=http://127.0.0.1:8000 NUVA_GOOGLE_E2E_DB_PATH="$TEST_DB" php scripts/google-login-e2e-test.php
 */

$base = rtrim((string) getenv('NUVA_GOOGLE_E2E_BASE_URL'), '/');
$dbPath = realpath((string) getenv('NUVA_GOOGLE_E2E_DB_PATH'));
if ($base === '' || $dbPath === false || !in_array(parse_url($base, PHP_URL_HOST), ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, "Set NUVA_GOOGLE_E2E_BASE_URL (localhost) and NUVA_GOOGLE_E2E_DB_PATH (temporary SQLite).\n");
    exit(2);
}
$db = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('DELETE FROM rate_limits');

/** @return array{status: int, headers: array<string, string>, body: string} */
function http(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $context = stream_context_create(['http' => [
        'method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body,
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 15,
    ]]);
    $content = (string) @file_get_contents($url, false, $context);
    $status = 0;
    $parsed = [];
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $status = (int) $m[1];
            $parsed = [];
        } elseif (str_contains($line, ':')) {
            [$k, $v] = explode(':', $line, 2);
            $parsed[strtolower(trim($k))] = trim($v);
        }
    }

    return ['status' => $status, 'headers' => $parsed, 'body' => $content];
}

/**
 * Drives the browser side: app → Google (choosing $identity) → app callback.
 * @return array{fragment: array<string, string>, stateCookie: string, cleared: string}
 */
function googleFlow(string $base, array $identity, bool $sendCookie = true): array
{
    $start = http('GET', "{$base}/api/auth/google/redirect");
    $setCookie = $start['headers']['set-cookie'] ?? '';
    preg_match('/nuva_google_state=([^;]*)/', $setCookie, $m);
    $consent = http('GET', ($start['headers']['location'] ?? '') . '&as=' . urlencode(json_encode($identity)));
    $callback = http('GET', $consent['headers']['location'] ?? '', $sendCookie ? ['Cookie: nuva_google_state=' . ($m[1] ?? '')] : []);
    parse_str((string) parse_url($callback['headers']['location'] ?? '', PHP_URL_FRAGMENT), $fragment);

    return ['fragment' => $fragment, 'stateCookie' => $setCookie, 'cleared' => $callback['headers']['set-cookie'] ?? '', 'location' => $callback['headers']['location'] ?? ''];
}

function exchange(string $base, string $code): array
{
    $response = http('POST', "{$base}/api/auth/google/exchange", ['Content-Type: application/json', 'Accept: application/json'], json_encode(['code' => $code]));

    return ['status' => $response['status'], 'json' => json_decode($response['body'], true)];
}

$passed = 0;
$failed = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

$suffix = bin2hex(random_bytes(3));
$newEmail = "google-{$suffix}@gmail.com";

echo "Nüva Google sign-in E2E (fake provider)\n\n";

$providers = json_decode(http('GET', "{$base}/api/auth/providers")['body'], true);
check('Provider is advertised to the login page', ($providers['google'] ?? false) === true);

$flow = googleFlow($base, ['sub' => "sub-{$suffix}", 'email' => $newEmail, 'name' => 'Paciente Google']);
check('State cookie is HttpOnly, SameSite=Lax and scoped', str_contains($flow['stateCookie'], 'HttpOnly') && str_contains($flow['stateCookie'], 'SameSite=Lax') && str_contains($flow['stateCookie'], 'Path=/api/auth/google'), $flow['stateCookie']);
check('Callback returns a one-time code in the URL fragment, never a token', isset($flow['fragment']['google_code']) && str_starts_with($flow['location'], '/login#') && !str_contains($flow['location'], 'token'), $flow['location']);
check('Callback clears the state cookie', str_contains($flow['cleared'], 'Max-Age=0'));

$first = exchange($base, $flow['fragment']['google_code'] ?? '');
check('New person is registered as a patient and signed in', $first['status'] === 200 && ($first['json']['user']['role'] ?? null) === 'patient' && ($first['json']['user']['email'] ?? null) === $newEmail, "HTTP {$first['status']}");
check('Login code works only once', exchange($base, $flow['fragment']['google_code'] ?? '')['status'] === 401);
$me = http('GET', "{$base}/api/user", ['Authorization: Bearer ' . ($first['json']['token'] ?? ''), 'Accept: application/json']);
check('Issued token opens a session', $me['status'] === 200);

$again = googleFlow($base, ['sub' => "sub-{$suffix}", 'email' => $newEmail]);
$second = exchange($base, $again['fragment']['google_code'] ?? '');
$count = (int) $db->query("SELECT COUNT(*) FROM users WHERE email = " . $db->quote($newEmail))->fetchColumn();
check('Returning user signs into the same account', $second['status'] === 200 && ($second['json']['user']['id'] ?? 0) === ($first['json']['user']['id'] ?? -1) && $count === 1);

$csrf = googleFlow($base, ['sub' => "sub-{$suffix}", 'email' => $newEmail], false);
check('Callback without the browser\'s state cookie is refused (login CSRF)', isset($csrf['fragment']['google_error']) && !isset($csrf['fragment']['google_code']), $csrf['fragment']['google_error'] ?? '');

$existing = googleFlow($base, ['sub' => "sub-alegra-{$suffix}", 'email' => 'alegra@example.com']);
$linked = exchange($base, $existing['fragment']['google_code'] ?? '');
check('Existing active account is linked by verified e-mail', $linked['status'] === 200 && ($linked['json']['user']['email'] ?? null) === 'alegra@example.com');

$pendingEmail = "pendente-{$suffix}@gmail.com";
$db->prepare("INSERT INTO users (name, email, password, role, activated_at, created_at, updated_at) VALUES ('Pendente', ?, 'x', 'patient', NULL, '2026-01-01 00:00:00', '2026-01-01 00:00:00')")->execute([$pendingEmail]);
$pending = googleFlow($base, ['sub' => "sub-pend-{$suffix}", 'email' => $pendingEmail]);
check('Clinic account not yet activated is refused', str_contains($pending['fragment']['google_error'] ?? '', 'ativação'), $pending['fragment']['google_error'] ?? '');

$unverified = googleFlow($base, ['sub' => "sub-unv-{$suffix}", 'email' => "naoverificado-{$suffix}@empresa.com", 'verified' => false]);
check('Unverified Google e-mail is refused', str_contains($unverified['fragment']['google_error'] ?? '', 'não confirmou'), $unverified['fragment']['google_error'] ?? '');

$hijack = googleFlow($base, ['sub' => "outra-conta-{$suffix}", 'email' => $newEmail]);
check('E-mail already linked to another Google account is refused', str_contains($hijack['fragment']['google_error'] ?? '', 'outra conta Google'), $hijack['fragment']['google_error'] ?? '');

$cancel = http('GET', "{$base}/api/auth/google/callback?error=access_denied");
parse_str((string) parse_url($cancel['headers']['location'] ?? '', PHP_URL_FRAGMENT), $canceled);
check('User canceling at Google returns a friendly message', str_contains($canceled['google_error'] ?? '', 'cancelado'));

$audit = (int) $db->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('login.succeeded_google', 'account.registered_google', 'account.google_linked')")->fetchColumn();
check('Google sign-ins are audited', $audit >= 4, "{$audit} registros");

// Cleanup: test accounts and the link added to the demo patient.
foreach ([$newEmail, $pendingEmail] as $email) {
    $db->prepare('DELETE FROM patients WHERE user_id IN (SELECT id FROM users WHERE email = ?)')->execute([$email]);
    $db->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
}
$db->exec("UPDATE users SET google_sub = NULL WHERE email = 'alegra@example.com'");

echo "\nResult: {$passed}/" . ($passed + $failed) . ' passed' . ($failed ? ", {$failed} failed.\n" : ".\n");
exit($failed ? 1 : 0);
