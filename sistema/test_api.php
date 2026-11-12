<?php

declare(strict_types=1);

/**
 * Integration checks of the infrastructure against a seeded, temporary SQLite.
 *
 *   NUVA_TEST_DB_PATH=/private/tmp/nuva-tests/nuva.sqlite php -d variables_order=EGPCS test_api.php
 */

$databasePath = (string) getenv('NUVA_TEST_DB_PATH');
$realDatabasePath = $databasePath !== '' ? realpath($databasePath) : false;
if ($realDatabasePath === false || $realDatabasePath === realpath(__DIR__ . '/database/database.sqlite')) {
    fwrite(STDERR, "NUVA_TEST_DB_PATH must point to an existing temporary SQLite database.\n");
    exit(2);
}
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $realDatabasePath);
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $realDatabasePath;

use App\Application\Identity\AuthenticateSession;
use App\Application\Identity\PasswordResetLinks;
use App\Application\Identity\SessionIssuer;
use App\Application\Shared\RateLimiter;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\SessionRepository;
use App\Domain\Shared\Unauthenticated;
use App\Infrastructure\Persistence\Db;

$container = require __DIR__ . '/bootstrap/app.php';
$db = $container->get(Db::class);

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runTest(string $name, callable $test): void
{
    global $passed, $failed;
    try {
        $test();
        $passed++;
        echo "PASS {$name}\n";
    } catch (Throwable $error) {
        $failed++;
        echo "FAIL {$name} — {$error->getMessage()}\n";
    }
}

echo "Nüva infrastructure integration test (temporary SQLite)\n\n";

runTest('Password hashing', static function () use ($container): void {
    $hasher = $container->get(PasswordHasher::class);
    $hash = $hasher->hash('senha123');
    assertTest($hasher->verify('senha123', $hash), 'valid password was rejected');
    assertTest(!$hasher->verify('senha_errada', $hash), 'invalid password was accepted');
});

$professionalUserId = (int) $db->one("SELECT id FROM users WHERE email = 'dr.gabriel@nuva.com.br'")['id'];

runTest('Session lifecycle: issue, authenticate, revoke', static function () use ($container, $professionalUserId): void {
    $token = $container->get(SessionIssuer::class)->issue($professionalUserId);
    $actor = $container->get(AuthenticateSession::class)($token, '127.0.0.1');
    assertTest($actor->userId === $professionalUserId, 'token did not resolve the expected user');
    assertTest($actor->professionalId !== null, 'professional profile was not attached');

    $container->get(SessionRepository::class)->revoke(hash('sha256', $token));
    try {
        $container->get(AuthenticateSession::class)($token, '127.0.0.1');
        throw new RuntimeException('revoked token is still valid');
    } catch (Unauthenticated) {
    }
});

runTest('Session expires when idle and at its absolute limit', static function () use ($container, $db, $professionalUserId): void {
    $authenticate = $container->get(AuthenticateSession::class);
    foreach (['last_used_at' => '-3 hours', 'expires_at' => '-1 minute'] as $column => $shift) {
        $token = $container->get(SessionIssuer::class)->issue($professionalUserId);
        $db->execute("UPDATE personal_access_tokens SET {$column} = ? WHERE token = ?", [date('Y-m-d H:i:s', strtotime($shift)), hash('sha256', $token)]);
        try {
            $authenticate($token, null);
            throw new RuntimeException("session survived {$column} {$shift}");
        } catch (Unauthenticated) {
        }
        assertTest($db->one('SELECT id FROM personal_access_tokens WHERE token = ?', [hash('sha256', $token)]) === null, 'expired session was not removed');
    }
});

runTest('Seeded clinical relationships', static function () use ($db): void {
    assertTest(count($db->all('SELECT id FROM procedures')) >= 3, 'seed procedures were not found');
    assertTest(count($db->all('SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id')) >= 3, 'seed patients were not found');
    assertTest($db->all('SELECT id FROM medical_records') !== [], 'seed medical record was not found');
    assertTest($db->all('SELECT id FROM appointments') !== [], 'seed appointment was not found');
});

$resetEmail = 'test-expiracao@nuva.example';
runTest('Password-reset links: hashed, single-use and expiring', static function () use ($container, $db, $resetEmail): void {
    $links = $container->get(PasswordResetLinks::class);
    parse_str((string) parse_url($links->issue($resetEmail), PHP_URL_QUERY), $query);
    $token = (string) $query['token'];
    assertTest($links->isValid($resetEmail, $token), 'fresh link was rejected');

    $stored = $db->one('SELECT token FROM password_reset_tokens WHERE email = ?', [$resetEmail]);
    assertTest(($stored['token'] ?? null) === hash('sha256', $token), 'token is not stored as a SHA-256 hash');
    assertTest(!$links->isValid($resetEmail, hash('sha256', $token)), 'stored hash was accepted as a token');

    $setIssuedAt = static fn (int $secondsAgo) => $db->execute(
        'UPDATE password_reset_tokens SET created_at = ? WHERE email = ?',
        [date('Y-m-d H:i:s', time() - $secondsAgo), $resetEmail],
    );
    $setIssuedAt(PasswordResetLinks::TTL_HOURS * 3600 - 60);
    assertTest($links->isValid($resetEmail, $token), 'link was rejected before its expiry');
    $setIssuedAt(PasswordResetLinks::TTL_HOURS * 3600 + 60);
    assertTest(!$links->isValid($resetEmail, $token), 'expired link was accepted');
});
$db->execute('DELETE FROM password_reset_tokens WHERE email = ?', [$resetEmail]);

runTest('Rate limiter counts per key and window', static function () use ($container): void {
    $limiter = $container->get(RateLimiter::class);
    $key = 'test:' . bin2hex(random_bytes(4));
    for ($i = 1; $i <= 3; $i++) {
        assertTest($limiter->hit($key, 3, 60) === null, "hit {$i} was blocked");
    }
    assertTest(($limiter->blockedFor($key, 3) ?? 0) > 0, 'key over the limit is not blocked');
    assertTest(($limiter->hit($key, 3, 60) ?? 0) > 0, 'fourth hit was not blocked');
    $limiter->clear($key);
    assertTest($limiter->blockedFor($key, 3) === null, 'clear did not reset the key');
});

runTest('Timestamps use the clinic timezone', static function () use ($container, $db): void {
    $clinicNow = new DateTimeImmutable('now', new DateTimeZone($container->get(App\Infrastructure\Config::class)->timezone()));
    assertTest(date_default_timezone_get() === $clinicNow->getTimezone()->getName(), 'PHP timezone is not the clinic timezone');
    $key = 'tz:' . bin2hex(random_bytes(4));
    $container->get(RateLimiter::class)->hit($key, 1, 60);
    $resetAt = new DateTimeImmutable((string) $db->one('SELECT reset_at FROM rate_limits WHERE rate_key = ?', [$key])['reset_at']);
    assertTest(abs($resetAt->getTimestamp() - $clinicNow->getTimestamp() - 60) < 5, 'stored time is not in the clinic timezone');
    $db->execute('DELETE FROM rate_limits WHERE rate_key = ?', [$key]);
});

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}
echo ".\n";
