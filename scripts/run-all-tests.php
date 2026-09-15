<?php

declare(strict_types=1);

/**
 * Runner Geral de Testes e Análise Automatizada de Todo o Código.
 * Executa todas as baterias de testes:
 *  - 1. Lint / Análise de Sintaxe de 100% dos arquivos PHP
 *  - 2. Deploy-Check do Site / CMS Principal
 *  - 3. Testes Unitários e de Repositório (MySQL)
 *  - 4. Testes E2E HTTP do Site & Painel Administrativo
 *  - 5. Deploy-Check do Sistema Clínico (Nüva)
 *  - 6. Testes Core do Banco e Autenticação do Sistema
 *  - 7. Smoke Tests de Rotas do Sistema
 *  - 8. Testes E2E Completos da API do Sistema
 *
 * Uso: php scripts/run-all-tests.php
 */

$root = dirname(__DIR__);
$totalBatteries = 0;
$passedBatteries = 0;
$failedBatteries = 0;

function runBattery(string $title, string $command, array $env = [], ?string $cwd = null): bool
{
    global $totalBatteries, $passedBatteries, $failedBatteries, $root;
    $totalBatteries++;

    $cwd = $cwd ?: $root;
    echo "\n" . str_repeat('=', 60) . "\n";
    echo " BATERIA {$totalBatteries}: {$title}\n";
    echo str_repeat('=', 60) . "\n";

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $mergedEnv = array_merge($_ENV, getenv(), $env);
    $process = proc_open($command, $descriptorSpec, $pipes, $cwd, $mergedEnv);

    if (!is_resource($process)) {
        echo "❌ Erro ao iniciar processo da bateria: {$command}\n";
        $failedBatteries++;
        return false;
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($stdout) {
        echo trim($stdout) . "\n";
    }
    if ($stderr) {
        echo "STDERR:\n" . trim($stderr) . "\n";
    }

    if ($exitCode === 0) {
        echo "✅ {$title}: PASSOU (Exit Code 0)\n";
        $passedBatteries++;
        return true;
    } else {
        echo "❌ {$title}: FALHOU (Exit Code {$exitCode})\n";
        $failedBatteries++;
        return false;
    }
}

echo "############################################################\n";
echo "#          INICIANDO TESTES E ANÁLISE COMPLETA            #\n";
echo "############################################################\n";

// 1. Sintaxe de todos os arquivos PHP
runBattery(
    'Análise de Sintaxe PHP (php -l em todos os arquivos)',
    'find . -name "*.php" -not -path "*/vendor/*" -exec php -l {} +'
);

// 2. Deploy-check do site principal
runBattery(
    'Validação de Ambiente e Requisitos do Site Principal',
    'php scripts/deploy-check.php'
);

// 3. Suíte de Testes Unitários e de Repositórios (MySQL)
runBattery(
    'Testes Unitários, Repositórios MySQL e ImageOptimizer',
    'php scripts/app-test-suite.php'
);

// 4. Testes E2E HTTP do Site e Painel Administrativo
$serverHost = '127.0.0.1:8995';
$serverCmd = sprintf('php -S %s -t public', $serverHost);
$serverProc = proc_open($serverCmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $sPipes, $root);
usleep(300000); // 300ms para iniciar o servidor

runBattery(
    'Testes E2E HTTP do Site Público e Painel CMS',
    sprintf('APP_TEST_BASE_URL=http://%s php scripts/http-e2e-test.php', $serverHost),
    ['APP_TEST_BASE_URL' => "http://{$serverHost}"]
);

runBattery(
    'Auditoria de QA, Segurança Cibernética e Usabilidade',
    sprintf('APP_URL=http://%s php scripts/qa-security-audit.php', $serverHost),
    ['APP_URL' => "http://{$serverHost}"]
);

if (is_resource($serverProc)) {
    proc_terminate($serverProc);
    proc_close($serverProc);
}

// 5. Deploy-check do Sistema Clínico (Nüva)
runBattery(
    'Validação de Requisitos e Deploy do Sistema Clínico',
    'php sistema/scripts/deploy-check.php'
);

// 6. Testes Core do Banco do Sistema (SQLite isolado temporário)
$tmpDb = sys_get_temp_dir() . '/nuva_unified_test.sqlite';
@unlink($tmpDb);
touch($tmpDb);

$migrateCmd = sprintf('DB_CONNECTION=sqlite DB_DATABASE=%s php sistema/database/migrate.php', escapeshellarg($tmpDb));
$seedCmd = sprintf('DB_CONNECTION=sqlite DB_DATABASE=%s php sistema/database/seed.php', escapeshellarg($tmpDb));
shell_exec($migrateCmd);
shell_exec($seedCmd);

runBattery(
    'Testes de Auth, Tokens e Relacionamentos do Sistema Clínico',
    sprintf('NUVA_TEST_DB_PATH=%s php -d variables_order=EGPCS sistema/test_api.php', escapeshellarg($tmpDb)),
    [
        'NUVA_TEST_DB_PATH' => $tmpDb,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $tmpDb
    ]
);

// 7 e 8. Smoke Tests e E2E da API do Sistema com Servidor Embutido
$sistemaHost = '127.0.0.1:8996';
$sistemaServerCmd = sprintf('php -S %s -t sistema/public', $sistemaHost);
$sistemaProc = proc_open($sistemaServerCmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $sysPipes, $root, array_merge($_ENV, getenv(), [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $tmpDb
]));
usleep(500000); // 500ms para iniciar o servidor

runBattery(
    'Smoke Test de Rotas do Sistema Clínico',
    sprintf('NUVA_TEST_BASE_URL=http://%s php sistema/scripts/route-smoke-test.php', $sistemaHost),
    ['NUVA_TEST_BASE_URL' => "http://{$sistemaHost}"]
);

runBattery(
    'Testes E2E Mutantes e Autenticados da API do Sistema Clínico',
    sprintf('NUVA_E2E_BASE_URL=http://%s NUVA_E2E_DB_PATH=%s NUVA_E2E_ALLOW_MUTATIONS=1 php sistema/scripts/api-e2e-test.php', $sistemaHost, escapeshellarg($tmpDb)),
    [
        'NUVA_E2E_BASE_URL' => "http://{$sistemaHost}",
        'NUVA_E2E_DB_PATH' => $tmpDb,
        'NUVA_E2E_ALLOW_MUTATIONS' => '1',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $tmpDb
    ]
);

if (is_resource($sistemaProc)) {
    proc_terminate($sistemaProc);
    proc_close($sistemaProc);
}
@unlink($tmpDb);

echo "\n" . str_repeat('#', 60) . "\n";
echo "                   RESUMO GERAL DOS TESTES\n";
echo str_repeat('#', 60) . "\n";
printf("Total de Baterias Executadas: %d\n", $totalBatteries);
printf("Baterias com Sucesso:         %d\n", $passedBatteries);
printf("Baterias com Falha:           %d\n", $failedBatteries);

if ($failedBatteries === 0) {
    echo "\n🎉 TODOS OS TESTES E ANÁLISES PASSARAM COM SUCESSO! (100% OK)\n\n";
    exit(0);
} else {
    echo "\n❌ EXISTEM FALHAS NAS BATERIAS ACIMA.\n\n";
    exit(1);
}
