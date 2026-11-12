<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

$driver = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'sqlite';
echo "Executando migrações no banco de dados ({$driver})...\n";

$schemaFile = ($driver === 'mysql') ? __DIR__ . '/schema_mysql.sql' : __DIR__ . '/schema.sql';
$schemaSql = file_get_contents($schemaFile);

$db = Database::getConnection();
$db->exec($schemaSql);

echo "✅ Tabelas criadas com sucesso no {$driver}!\n";

// --- Migrações incrementais (bancos criados antes da coluna existir) -------

$userColumns = $driver === 'mysql'
    ? array_column($db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field')
    : array_column($db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC), 'name');

if (!in_array('activated_at', $userColumns, true)) {
    $db->exec('ALTER TABLE users ADD COLUMN activated_at DATETIME NULL');

    // Contas ficam "pendentes de ativação" (activated_at nulo) apenas quando
    // nunca puderam ser usadas: pacientes cadastrados pelo profissional, que
    // recebiam senha aleatória, e que nunca abriram uma sessão. Todas as outras
    // contas já estão em uso e são marcadas como ativas.
    $db->exec(
        "UPDATE users SET activated_at = COALESCE(created_at, CURRENT_TIMESTAMP)
         WHERE NOT (
             role = 'patient'
             AND id IN (SELECT user_id FROM patients WHERE professional_id IS NOT NULL AND user_id IS NOT NULL)
             AND id NOT IN (SELECT user_id FROM personal_access_tokens)
         )"
    );
    $pending = (int) $db->query('SELECT COUNT(*) FROM users WHERE activated_at IS NULL')->fetchColumn();
    echo "✅ Coluna users.activated_at criada ({$pending} conta(s) pendente(s) de ativação).\n";
}
