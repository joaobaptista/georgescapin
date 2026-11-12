<?php

declare(strict_types=1);

/**
 * Script de Backup Automatizado do Banco de Dados MySQL
 * Clínica Dr. George Scapin & Sistema Clínico
 *
 * Uso:
 *   php scripts/backup-database.php
 *   php scripts/backup-database.php --database=clinica_george
 *   php scripts/backup-database.php --database=nuva
 *   php scripts/backup-database.php --all
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

// Configura fuso horário padrão para São Paulo
date_default_timezone_set('America/Sao_Paulo');

$startTime = microtime(true);
$root = dirname(__DIR__);

require_once $root . '/app/helpers.php';

// Cores ANSI para saída no terminal
$cReset  = "\033[0m";
$cBold   = "\033[1m";
$cGreen  = "\033[32m";
$cRed    = "\033[31m";
$cCyan   = "\033[36m";
$cYellow = "\033[33m";
$cGray   = "\033[90m";

echo "\n{$cBold}{$cCyan}============================================================{$cReset}\n";
echo "{$cBold}{$cCyan}    BACKUP AUTOMATIZADO DE BANCO DE DADOS MYSQL            {$cReset}\n";
echo "{$cBold}{$cCyan}    Clínica Dr. George Scapin                              {$cReset}\n";
echo "{$cBold}{$cCyan}============================================================{$cReset}\n\n";

// Parse CLI options
$options = getopt('', ['database:', 'all', 'help']);
if (isset($options['help'])) {
    echo "Opções disponíveis:\n";
    echo "  --database=<nome>  Especifica o banco a exportar (ex: clinica_george ou nuva)\n";
    echo "  --all              Exporta clinica_george e nuva (comportamento padrão)\n";
    echo "  --help             Exibe esta mensagem de ajuda\n\n";
    exit(0);
}

// Localizar o executável do mysqldump
function findMysqldump(): ?string
{
    $candidates = [
        'mysqldump',
        '/opt/homebrew/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/usr/bin/mysqldump',
        '/opt/lampp/bin/mysqldump',
        '/Applications/MAMP/Library/bin/mysqldump'
    ];

    foreach ($candidates as $bin) {
        $check = shell_exec("which " . escapeshellarg($bin) . " 2>/dev/null");
        if (!empty($check) && is_executable(trim($check))) {
            return trim($check);
        }
        if (is_file($bin) && is_executable($bin)) {
            return $bin;
        }
    }

    return null;
}

$mysqldumpPath = findMysqldump();
if (!$mysqldumpPath) {
    echo "{$cRed}[ERRO] O utilitário 'mysqldump' não foi encontrado no sistema.{$cReset}\n";
    echo "Certifique-se de que o MySQL Client está instalado e acessível no PATH.\n\n";
    exit(1);
}

// Configurações do banco a partir do .env / config
$host     = getenv('DB_HOST') ?: '127.0.0.1';
$port     = (int)(getenv('DB_PORT') ?: 3306);
$user     = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$charset  = getenv('DB_CHARSET') ?: 'utf8mb4';
$mainDb   = getenv('DB_DATABASE') ?: 'clinica_george';

$timestamp = date('Ymd_His');

// Definir quais bancos serão exportados
$databasesToBackup = [];
if (!empty($options['database'])) {
    $databasesToBackup[] = (string)$options['database'];
} else {
    // Por padrão faz o backup do principal e do nuva caso exista
    $databasesToBackup[] = $mainDb;

    try {
        $dsn = "mysql:host={$host};port={$port};charset={$charset}";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $stmt = $pdo->query("SHOW DATABASES LIKE 'nuva'");
        if ($stmt->fetch()) {
            $databasesToBackup[] = 'nuva';
        }
    } catch (\Throwable $e) {
        // Se falhar a listagem, mantém apenas o principal
    }
}

$totalSuccess = 0;
$totalFailed  = 0;

foreach ($databasesToBackup as $dbName) {
    echo "{$cBold}→ Processando banco: {$cYellow}{$dbName}{$cReset}\n";

    // Conectar e inspecionar tabelas via PDO
    $tableStats = [];
    $totalRows = 0;
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = " . $pdo->quote($dbName) . " ORDER BY table_name ASC");
        $tableList = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tableList as $tbl) {
            try {
                $cntStmt = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`");
                $count = (int)$cntStmt->fetchColumn();
            } catch (\Throwable $t) {
                $count = 0;
            }
            $tableStats[$tbl] = $count;
            $totalRows += $count;
        }
    } catch (\Throwable $e) {
        echo "  {$cRed}[AVISO] Não foi possível consultar metadados via PDO: " . $e->getMessage() . "{$cReset}\n";
    }

    echo "  Tabelas: " . count($tableStats) . " | Registros totais: {$totalRows}\n";
    if (!empty($tableStats)) {
        $preview = [];
        foreach ($tableStats as $tbl => $cnt) {
            $preview[] = "{$tbl} ({$cnt})";
        }
        echo "  {$cGray}Detalhes: " . implode(', ', $preview) . "{$cReset}\n";
    }

    // Dumps contêm dados de pacientes: ficam fora das pastas servidas pela web
    // (storage/ é bloqueado por .htaccess; no Nginx, negue /storage) ou em
    // BACKUP_DIR, de preferência fora da raiz do site.
    $backupDir = rtrim((string) (getenv('BACKUP_DIR') ?: $root . '/storage/backups'), '/');
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
        echo "{$cRed}[ERRO] Não foi possível criar o diretório de backup: {$backupDir}{$cReset}\n";
        exit(1);
    }
    $destFiles = [
        "{$backupDir}/backup_{$dbName}_latest.sql",
        "{$backupDir}/backup_{$dbName}_{$timestamp}.sql",
    ];

    $primaryDest = $destFiles[0];

    // Construção do comando mysqldump seguro
    $cmdParts = [
        escapeshellcmd($mysqldumpPath),
        '-h ' . escapeshellarg($host),
        '-P ' . escapeshellarg((string)$port),
        '-u ' . escapeshellarg($user),
    ];

    // A senha vai por MYSQL_PWD, não pela linha de comando: argumentos de
    // processo ficam visíveis para qualquer usuário do servidor (ps aux).
    putenv('MYSQL_PWD=' . $password);

    $cmdParts[] = '--default-character-set=' . escapeshellarg($charset);
    $cmdParts[] = '--single-transaction';
    $cmdParts[] = '--quick';
    $cmdParts[] = '--set-gtid-purged=OFF';
    $cmdParts[] = escapeshellarg($dbName);

    $dumpCommand = 'umask 077 && ' . implode(' ', $cmdParts) . ' > ' . escapeshellarg($primaryDest);

    $cmdOutput = [];
    $exitCode = 0;
    exec($dumpCommand . ' 2>&1', $cmdOutput, $exitCode);

    if ($exitCode === 0 && is_file($primaryDest) && filesize($primaryDest) > 0) {
        $sizeBytes = filesize($primaryDest);
        $sizeKb = round($sizeBytes / 1024, 2);
        $sizeMb = round($sizeBytes / (1024 * 1024), 2);
        $sizeStr = $sizeMb >= 1.0 ? "{$sizeMb} MB" : "{$sizeKb} KB";

        // Cria as cópias timestamped
        for ($i = 1; $i < count($destFiles); $i++) {
            copy($primaryDest, $destFiles[$i]);
            chmod($destFiles[$i], 0600);
        }

        echo "  {$cGreen}✓ Backup gerado com sucesso!{$cReset}\n";
        echo "  Arquivo Principal:   {$cBold}" . basename($primaryDest) . "{$cReset} ({$sizeStr})\n";
        if (isset($destFiles[1])) {
            echo "  Arquivo Timestamped: {$cBold}" . basename($destFiles[1]) . "{$cReset}\n";
        }
        $totalSuccess++;
    } else {
        echo "  {$cRed}✗ Falha ao executar mysqldump (Exit Code {$exitCode}){$cReset}\n";
        if (!empty($cmdOutput)) {
            echo "  Mensagem de erro: " . implode("\n  ", $cmdOutput) . "\n";
        }
        $totalFailed++;
    }

    echo "\n";
}

$elapsed = round((microtime(true) - $startTime), 3);

echo "{$cBold}Resumo da Operação:{$cReset}\n";
echo "  Bancos com sucesso: {$cGreen}{$totalSuccess}{$cReset}\n";
if ($totalFailed > 0) {
    echo "  Bancos com falha:   {$cRed}{$totalFailed}{$cReset}\n";
}
echo "  Tempo de execução:  {$elapsed}s\n";
echo "  Timestamp:          {$timestamp}\n\n";

if ($totalFailed > 0) {
    exit(1);
}

exit(0);
