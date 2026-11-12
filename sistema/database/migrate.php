<?php

declare(strict_types=1);

/**
 * Creates the schema and applies incremental changes. Safe to run repeatedly:
 * run it after every deploy (composer migrate).
 */

use App\Infrastructure\Persistence\Db;

$container = require dirname(__DIR__) . '/bootstrap/app.php';
$db = $container->get(Db::class);
$pdo = $db->pdo;

echo "Executando migrações no banco de dados ({$db->driver})...\n";

$pdo->exec((string) file_get_contents(__DIR__ . ($db->driver === 'mysql' ? '/schema_mysql.sql' : '/schema.sql')));
echo "✅ Tabelas criadas ou já existentes no {$db->driver}.\n";

$columns = static fn (string $table): array => $db->driver === 'mysql'
    ? array_column($db->all("SHOW COLUMNS FROM {$table}"), 'Field')
    : array_column($db->all("PRAGMA table_info({$table})"), 'name');

// 2026-11: users.activated_at — accounts registered by the clinic start pending.
if (!in_array('activated_at', $columns('users'), true)) {
    $pdo->exec('ALTER TABLE users ADD COLUMN activated_at DATETIME NULL');

    // Pending only when the account could never be used: patients registered
    // by a professional (random password) who never opened a session.
    $pdo->exec(
        "UPDATE users SET activated_at = COALESCE(created_at, CURRENT_TIMESTAMP)
         WHERE NOT (
             role = 'patient'
             AND id IN (SELECT user_id FROM patients WHERE professional_id IS NOT NULL AND user_id IS NOT NULL)
             AND id NOT IN (SELECT user_id FROM personal_access_tokens)
         )"
    );
    $pending = (int) $db->one('SELECT COUNT(*) AS n FROM users WHERE activated_at IS NULL')['n'];
    echo "✅ Coluna users.activated_at criada ({$pending} conta(s) pendente(s) de ativação).\n";
}

// 2026-11: personal_access_tokens.last_used_at — idle timeout of sessions.
if (!in_array('last_used_at', $columns('personal_access_tokens'), true)) {
    $pdo->exec('ALTER TABLE personal_access_tokens ADD COLUMN last_used_at DATETIME NULL');
    // Sessions from before the 12-hour policy had no expiry: end them all.
    $ended = $db->execute('DELETE FROM personal_access_tokens');
    echo "✅ Coluna personal_access_tokens.last_used_at criada ({$ended} sessão(ões) antiga(s) encerrada(s)).\n";
}
