<?php

declare(strict_types=1);

/**
 * Gera um link de redefinição de senha para QUALQUER conta (uso exclusivo da
 * administração, no servidor). O sistema não envia mensagens: entregue o link
 * por um canal confiável, depois de confirmar a identidade da pessoa. Vale por
 * 72 horas, funciona uma única vez e encerra as sessões abertas da conta.
 *
 *   php scripts/password-reset-link.php --email=pessoa@exemplo.com [--base-url=https://sistema.clinica.com.br]
 */

use App\Application\Identity\PasswordResetLinks;
use App\Domain\Identity\UserRepository;
use App\Infrastructure\Config;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$container = require dirname(__DIR__) . '/bootstrap/app.php';

$options = getopt('', ['email:', 'base-url:']);
$email = trim((string) ($options['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Informe um --email válido.\n");
    exit(2);
}

$user = $container->get(UserRepository::class)->findByEmail($email);
if ($user === null) {
    fwrite(STDERR, "Nenhuma conta encontrada com o e-mail {$email}.\n");
    exit(1);
}

$baseUrl = rtrim((string) ($options['base-url'] ?? $container->get(Config::class)->appUrl()), '/');
$path = $container->get(PasswordResetLinks::class)->issue($email);

echo "Conta: {$user->name} ({$user->role->value})\n";
echo 'Link (válido por ' . PasswordResetLinks::TTL_HOURS . " horas, uso único):\n";
echo $baseUrl . $path . "\n";
