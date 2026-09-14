<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

$driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
echo "Executando migrações no banco de dados ({$driver})...\n";

$schemaFile = ($driver === 'mysql') ? __DIR__ . '/schema_mysql.sql' : __DIR__ . '/schema.sql';
$schemaSql = file_get_contents($schemaFile);

$db = Database::getConnection();
$db->exec($schemaSql);

echo "✅ Tabelas criadas com sucesso no {$driver}!\n";
