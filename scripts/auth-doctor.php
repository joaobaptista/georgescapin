<?php

declare(strict_types=1);

/**
 * Script de Diagnóstico, Resiliência e Gestão de Autenticação / Senhas
 * Clínica Dr. George Scapin
 *
 * Uso:
 *   php scripts/auth-doctor.php
 *   php scripts/auth-doctor.php --sync-seeds
 *   php scripts/auth-doctor.php --reset-password=123
 *   php scripts/auth-doctor.php --reset-password=123 --email=admin@admin.com
 *   php scripts/auth-doctor.php --test-auth
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/app/helpers.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Security\Auth;

// Cores ANSI
$cReset  = "\033[0m";
$cBold   = "\033[1m";
$cGreen  = "\033[32m";
$cRed    = "\033[31m";
$cCyan   = "\033[36m";
$cYellow = "\033[33m";
$cGray   = "\033[90m";

echo "\n{$cBold}{$cCyan}============================================================{$cReset}\n";
echo "{$cBold}{$cCyan}    AUTH DOCTOR — ESPECIALISTA EM LOGIN E SENHAS           {$cReset}\n";
echo "{$cBold}{$cCyan}    Diagnóstico, Resiliência e Correção em Produção        {$cReset}\n";
echo "{$cBold}{$cCyan}============================================================{$cReset}\n\n";

$options = getopt('', ['sync-seeds', 'reset-password:', 'email:', 'test-auth', 'help']);

if (isset($options['help'])) {
    echo "Comandos disponíveis:\n";
    echo "  php scripts/auth-doctor.php                 Diagnóstico completo do ambiente e usuários\n";
    echo "  php scripts/auth-doctor.php --sync-seeds    Cria/atualiza os admins padrão com a senha '123'\n";
    echo "  php scripts/auth-doctor.php --reset-password=<senha> [--email=<email>]\n";
    echo "  php scripts/auth-doctor.php --test-auth     Testa a autenticação adaptativa (123 / admin123)\n";
    echo "\n";
    exit(0);
}

// -------------------------------------------------------------
// 1. Diagnóstico do Ambiente PHP e Sessões
// -------------------------------------------------------------
echo "{$cBold}1. Verificação de Ambiente PHP e Sessões{$cReset}\n";

$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
printf("  [%s] Versão do PHP: %s (>= 7.4 requerido)\n", $phpOk ? "{$cGreen}PASS{$cReset}" : "{$cRed}FAIL{$cReset}", PHP_VERSION);

$sessionSavePath = session_save_path();
$isDefaultWritable = !empty($sessionSavePath) && is_dir($sessionSavePath) && is_writable($sessionSavePath);
$storageDir = $root . '/storage/sessions';
$isStorageWritable = is_dir($storageDir) && is_writable($storageDir);

if (!$isStorageWritable) {
    @mkdir($storageDir, 0700, true);
    $isStorageWritable = is_dir($storageDir) && is_writable($storageDir);
}

$sessionOk = $isDefaultWritable || $isStorageWritable;
printf(
    "  [%s] Diretório de Sessões Gravável: %s%s\n",
    $sessionOk ? "{$cGreen}PASS{$cReset}" : "{$cRed}FAIL{$cReset}",
    $isDefaultWritable ? "Padrão do Sistema ({$sessionSavePath})" : "Fallback Local ({$storageDir})",
    (!$isDefaultWritable && $isStorageWritable) ? " {$cYellow}(Fallback Ativo e Seguro){$cReset}" : ""
);

$httpsCheck = is_https_request();
printf("  [%s] Detecção HTTPS / Proxy: %s\n", "{$cCyan}INFO{$cReset}", $httpsCheck ? "Ativo (HTTPS)" : "Inativo (HTTP local ou ambiente dev)");

echo "\n";

// -------------------------------------------------------------
// 2. Conexão com o Banco de Dados MySQL
// -------------------------------------------------------------
echo "{$cBold}2. Conexão com o Banco de Dados MySQL{$cReset}\n";

$pdo = null;
try {
    $pdo = Connection::getInstance();
    $dbName = getenv('DB_DATABASE') ?: 'clinica_george';
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    echo "  [{$cGreen}PASS{$cReset}] Conexão estabelecida com sucesso no banco '{$cBold}{$dbName}{$cReset}' em '{$host}'.\n";
} catch (\Throwable $e) {
    echo "  [{$cRed}FAIL{$cReset}] Erro ao conectar ao MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// -------------------------------------------------------------
// 3. Execução de Comandos Específicos se Solicitados
// -------------------------------------------------------------
if (isset($options['sync-seeds'])) {
    echo "\n{$cBold}→ Executando sincronização de administradores padrão (senha: 123)...{$cReset}\n";
    Auth::ensureDefaultAdmins('123');
    echo "  [{$cGreen}OK{$cReset}] Usuários admin@drgeorgescapin.com.br e admin@admin.com configurados com sucesso.\n\n";
}

if (isset($options['reset-password'])) {
    $newPass = (string)$options['reset-password'];
    $targetEmail = !empty($options['email']) ? (string)$options['email'] : null;

    echo "\n{$cBold}→ Redefinindo senha para: '{$newPass}'...{$cReset}\n";
    if ($targetEmail) {
        $ok = Auth::resetPassword($targetEmail, $newPass);
        if ($ok) {
            echo "  [{$cGreen}OK{$cReset}] Senha do usuário '{$targetEmail}' alterada com sucesso.\n\n";
        } else {
            echo "  [{$cRed}FAIL{$cReset}] Usuário '{$targetEmail}' não encontrado no banco.\n\n";
        }
    } else {
        Auth::ensureDefaultAdmins($newPass);
        echo "  [{$cGreen}OK{$cReset}] Senhas dos administradores padrão (admin@drgeorgescapin.com.br e admin@admin.com) alteradas com sucesso.\n\n";
    }
}

// -------------------------------------------------------------
// 4. Análise da Tabela `users`
// -------------------------------------------------------------
echo "\n{$cBold}3. Usuários Cadastrados no Banco de Dados{$cReset}\n";

$users = $pdo->query("SELECT id, name, email, password, updated_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "  {$cYellow}[AVISO] Nenhum usuário encontrado na tabela 'users'!{$cReset}\n";
    echo "  Executando auto-provisionamento de emergência...\n";
    Auth::ensureDefaultAdmins('123');
    $users = $pdo->query("SELECT id, name, email, password, updated_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

echo "  " . str_pad("ID", 4) . str_pad("Nome", 22) . str_pad("E-mail", 32) . str_pad("Verifica '123'", 18) . "Verifica 'admin123'\n";
echo "  " . str_repeat("-", 88) . "\n";

foreach ($users as $u) {
    $match123 = password_verify('123', $u['password']) || $u['password'] === '123';
    $matchAdmin123 = password_verify('admin123', $u['password']) || $u['password'] === 'admin123';

    $col123 = $match123 ? "{$cGreen}SIM (Válida){$cReset}" : "{$cRed}NÃO{$cReset}";
    $colAdmin123 = $matchAdmin123 ? "{$cGreen}SIM (Válida){$cReset}" : "{$cGray}NÃO{$cReset}";

    printf(
        "  %-4d %-22s %-32s %-27s %-20s\n",
        (int)$u['id'],
        substr($u['name'], 0, 20),
        substr($u['email'], 0, 30),
        $col123,
        $colAdmin123
    );
}

// -------------------------------------------------------------
// 5. Teste da Camada de Autenticação Adaptativa
// -------------------------------------------------------------
echo "\n{$cBold}4. Testes de Autenticação Adaptativa (Auth::attempt){$cReset}\n";

$testCases = [
    ['Dr. George (admin@drgeorgescapin.com.br) com senha 123', 'admin@drgeorgescapin.com.br', '123'],
    ['Dr. George (admin@drgeorgescapin.com.br) com fallback admin123', 'admin@drgeorgescapin.com.br', 'admin123'],
    ['Administrador (admin@admin.com) com senha 123', 'admin@admin.com', '123'],
    ['Login rápido usando usuário "admin" com senha 123', 'admin', '123'],
    ['E-mail com maiúsculas mistas (ADMIN@ADMIN.COM)', 'ADMIN@ADMIN.COM', '123'],
    ['Senha incorreta proposital (deve falhar)', 'admin@admin.com', 'senha_errada_999'],
];

$passCount = 0;
foreach ($testCases as [$label, $id, $pass]) {
    $expectedSuccess = ($pass !== 'senha_errada_999');
    $actualResult = Auth::attempt($id, $pass);
    $ok = ($actualResult === $expectedSuccess);

    if ($ok) {
        $passCount++;
        echo "  [{$cGreen}PASS{$cReset}] {$label} → " . ($actualResult ? "Autenticado com Sucesso" : "Rejeitado Corretamente") . "\n";
    } else {
        echo "  [{$cRed}FAIL{$cReset}] {$label} → Resultado inesperado: " . ($actualResult ? "Autenticado (esperado falha)" : "Falhou (esperado sucesso)") . "\n";
    }
}

echo "\n{$cBold}Resumo do Diagnóstico:{$cReset}\n";
echo "  Bateria de testes: {$passCount}/" . count($testCases) . " aprovados\n";
echo "  Status Geral:      " . ($passCount === count($testCases) ? "{$cGreen}SISTEMA DE AUTENTICAÇÃO PRONTO E RESILIENTE{$cReset}" : "{$cRed}VERIFICAR FALHAS ACIMA{$cReset}") . "\n\n";

if ($passCount === count($testCases)) {
    exit(0);
}
exit(1);
