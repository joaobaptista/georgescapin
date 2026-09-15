<?php

declare(strict_types=1);

/**
 * Script de Build e Empacotamento de Release para Produção.
 *
 * Executa:
 * 1. Validação de sintaxe PHP (php -l)
 * 2. Execução da suíte completa de testes (scripts/run-all-tests.php)
 * 3. Auditoria de QA e Segurança
 * 4. Empacotamento em arquivo ZIP limpo e pronto para deploy (sem .git, .env de dev, logs, caches)
 *
 * Uso: php scripts/build-release.php
 */

$root = dirname(__DIR__);
$buildDir = $root . '/build';
$distName = 'georgescapin_release_' . date('Ymd_His') . '.zip';
$distPath = $buildDir . '/' . $distName;

echo "====================================================\n";
echo "   BUILD & PACKAGING: CLÍNICA DR. GEORGE SCAPIN     \n";
echo "====================================================\n\n";

// 1. Validação prévia
echo "🔍 1. Executando testes e verificações antes do build...\n";
$testCmd = 'php ' . escapeshellarg($root . '/scripts/run-all-tests.php');
passthru($testCmd, $testCode);

if ($testCode !== 0) {
    echo "\n❌ ERRO: Testes falharam. O build foi cancelado para evitar publicação com defeitos.\n";
    exit(1);
}

echo "\n📦 2. Preparando diretório de distribuição...\n";
if (!is_dir($buildDir)) {
    mkdir($buildDir, 0755, true);
}

// 2. Criação do ZIP de Release
echo "📦 3. Criando pacote de release: {$distName}...\n";

$zip = new ZipArchive();
if ($zip->open($distPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "❌ Erro ao criar arquivo ZIP em {$distPath}\n";
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$excludedPatterns = [
    '#^\.git(/|$)#',
    '#^\.gemini(/|$)#',
    '#^\.agents(/|$)#',
    '#^build(/|$)#',
    '#^\.DS_Store#',
    '#/\.DS_Store#',
    '#^\.env$#', // NUNCA empacota o .env local com credenciais de desenvolvimento
    '#\.log$#',
    '#\.sqlite$#',
    '#^site_george\.zip$#',
    '#^backup_.*\.sql$#',
];

$fileCount = 0;
foreach ($iterator as $item) {
    $subPathName = $iterator->getSubPathName();
    
    // Verifica se coincide com algum padrão de exclusão
    $skip = false;
    foreach ($excludedPatterns as $pattern) {
        if (preg_match($pattern, $subPathName)) {
            $skip = true;
            break;
        }
    }

    if ($skip) continue;

    $realPath = $item->getRealPath();
    if ($item->isDir()) {
        $zip->addEmptyDir($subPathName);
    } elseif ($item->isFile()) {
        $zip->addFile($realPath, $subPathName);
        $fileCount++;
    }
}

// Garante que .env.example seja empacotado
if (file_exists($root . '/.env.example')) {
    $zip->addFile($root . '/.env.example', '.env.example');
}

$zip->close();

$sizeMb = round(filesize($distPath) / (1024 * 1024), 2);

echo "\n====================================================\n";
echo "🎉 BUILD CONCLUÍDO COM SUCESSO!\n";
echo "====================================================\n";
echo "📁 Arquivo: {$distPath}\n";
echo "📊 Tamanho: {$sizeMb} MB ({$fileCount} arquivos empacotados)\n";
echo "🔒 Segurança: .env de desenvolvimento excluído (.env.example mantido)\n";
echo "🚀 Pronto para publicação e deploy em produção!\n";
echo "====================================================\n";
