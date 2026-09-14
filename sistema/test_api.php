<?php

declare(strict_types=1);

/**
 * Fast database-level checks for the PHP core.
 *
 * Use only an isolated, seeded SQLite database:
 *   NUVA_TEST_DB_PATH=/private/tmp/nuva-tests/nuva.sqlite php -d variables_order=EGPCS test_api.php
 */

$databasePath = (string) getenv('NUVA_TEST_DB_PATH');
$defaultDatabasePath = realpath(__DIR__ . '/database/database.sqlite');
$realDatabasePath = $databasePath !== '' ? realpath($databasePath) : false;

if ($realDatabasePath === false || $realDatabasePath === $defaultDatabasePath) {
    fwrite(STDERR, "NUVA_TEST_DB_PATH must point to an existing temporary SQLite database.\n");
    exit(2);
}

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=' . $realDatabasePath);
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $realDatabasePath;

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

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

echo "Nüva database core test (temporary SQLite)\n\n";

runTest('Auth hashing', static function (): void {
    $hash = Auth::hashPassword('senha123');
    assertTest(Auth::verifyPassword('senha123', $hash), 'valid password was rejected');
    assertTest(!Auth::verifyPassword('senha_errada', $hash), 'invalid password was accepted');
});

runTest('Bearer token lifecycle', static function (): void {
    $user = Database::fetchOne("SELECT id FROM users WHERE email = 'dr.gabriel@nuva.com.br'");
    assertTest($user !== null, 'seed professional was not found');

    $token = Auth::createToken((int) $user['id']);
    try {
        $validatedUser = Auth::validateToken($token);
        assertTest(is_array($validatedUser), 'token did not resolve a user');
        assertTest(($validatedUser['email'] ?? null) === 'dr.gabriel@nuva.com.br', 'token did not resolve the expected user');
        assertTest(!empty($validatedUser['professional']), 'professional profile was not attached');
    } finally {
        Auth::revokeToken($token);
    }

    assertTest(Auth::validateToken($token) === null, 'revoked token is still valid');
});

runTest('Seeded clinical relationships', static function (): void {
    $procedures = Database::fetchAll('SELECT id FROM procedures');
    $patients = Database::fetchAll('SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id');
    $records = Database::fetchAll('SELECT id FROM medical_records');
    $appointments = Database::fetchAll('SELECT id FROM appointments');

    assertTest(count($procedures) >= 3, 'seed procedures were not found');
    assertTest(count($patients) >= 3, 'seed patients were not found');
    assertTest($records !== [], 'seed medical record was not found');
    assertTest($appointments !== [], 'seed appointment was not found');
});

$resetEmail = 'test-expiracao@nuva.example';
runTest('Password-reset token expiration', static function () use ($resetEmail): void {
    $token = Auth::createPasswordResetToken($resetEmail);
    assertTest(Auth::validatePasswordResetToken($resetEmail, $token), 'fresh reset token was rejected');

    Database::execute(
        "UPDATE password_reset_tokens SET created_at = ? WHERE email = ?",
        [date('Y-m-d H:i:s', strtotime('-2 hours')), $resetEmail]
    );

    assertTest(!Auth::validatePasswordResetToken($resetEmail, $token), 'expired reset token was accepted');
});
Auth::deletePasswordResetToken($resetEmail);

runTest('Patient/professional scope fixtures', static function (): void {
    $professional = Database::fetchOne(
        "SELECT p.id FROM professionals p JOIN users u ON p.user_id = u.id WHERE u.email = 'dr.gabriel@nuva.com.br'"
    );
    $patient = Database::fetchOne(
        "SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.email = 'alegra@example.com'"
    );

    assertTest($professional !== null && $patient !== null, 'seed profiles were not found');
    $appointments = Database::fetchAll(
        'SELECT id FROM appointments WHERE professional_id = ? AND patient_id = ?',
        [(int) $professional['id'], (int) $patient['id']]
    );
    assertTest($appointments !== [], 'expected scoped appointment was not found');
});

$total = $passed + $failed;
echo "\nResult: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", {$failed} failed.\n";
    exit(1);
}

echo ".\n";
