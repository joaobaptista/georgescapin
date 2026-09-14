<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/app/helpers.php';

$checks = [];
$checks['PHP 7.4 ou superior'] = version_compare(PHP_VERSION, '7.4.0', '>=');
$checks['Extensão pdo_mysql'] = extension_loaded('pdo_mysql');
$checks['Extensão mbstring'] = extension_loaded('mbstring');
$checks['Extensão GD'] = extension_loaded('gd');
$checks['Arquivo .env presente'] = is_file($root . '/.env');
$checks['Diretório public/uploads gravável'] = is_dir($root . '/public/uploads') && is_writable($root . '/public/uploads');

$databaseChecks = false;
$schemaChecks = false;

if ($checks['Extensão pdo_mysql'] && $checks['Arquivo .env presente']) {
    $config = require $root . '/config/database.php';

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $databaseChecks = true;

        $requiredTables = [
            'users',
            'site_settings',
            'procedures',
            'posts',
            'hero_content',
            'clinic_content',
            'contact_messages',
            'newsletter_subscribers',
            'custom_pages',
            'menu_items',
        ];

        $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
        $statement = $pdo->prepare(
            "SELECT table_name FROM information_schema.tables\n" .
            "WHERE table_schema = ? AND table_name IN ({$placeholders})"
        );
        $statement->execute(array_merge([$config['database']], $requiredTables));
        $foundTables = $statement->fetchAll(PDO::FETCH_COLUMN);
        $schemaChecks = count(array_diff($requiredTables, $foundTables)) === 0;
    } catch (Throwable $exception) {
        $databaseChecks = false;
        $schemaChecks = false;
    }
}

$checks['Conexão com o MySQL'] = $databaseChecks;
$checks['Tabelas obrigatórias presentes'] = $schemaChecks;

$hasFailures = false;
foreach ($checks as $label => $passed) {
    $status = $passed ? 'OK' : 'FALHOU';
    echo "[{$status}] {$label}\n";
    $hasFailures = $hasFailures || !$passed;
}

exit($hasFailures ? 1 : 0);
