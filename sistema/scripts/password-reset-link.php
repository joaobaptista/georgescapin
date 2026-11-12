<?php

declare(strict_types=1);

/**
 * Gera um link de redefinição de senha para QUALQUER conta (paciente ou
 * profissional). Uso exclusivo da administração da clínica, no servidor.
 *
 * O sistema não envia e-mails nem mensagens: entregue o link à pessoa por um
 * canal confiável, depois de confirmar a identidade dela. O link vale por
 * 72 horas, funciona uma única vez e encerra as sessões abertas da conta.
 *
 * Uso:
 *   php scripts/password-reset-link.php --email=paciente@exemplo.com [--base-url=https://sistema.clinica.com.br]
 *
 * Sem --base-url, usa APP_URL do .env.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use Dotenv\Dotenv;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (file_exists(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$options = getopt('', ['email:', 'base-url:']);
$email = trim((string) ($options['email'] ?? ''));
$baseUrl = rtrim((string) ($options['base-url'] ?? ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '')), '/');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Informe um --email válido.\n");
    exit(2);
}

$user = Database::fetchOne('SELECT id, name, role FROM users WHERE email = ?', [$email]);
if (!$user) {
    fwrite(STDERR, "Nenhuma conta encontrada com o e-mail {$email}.\n");
    exit(1);
}

$token = Auth::createPasswordResetToken($email);
$path = '/ativar-conta?' . http_build_query(['email' => $email, 'token' => $token]);

echo "Conta: {$user['name']} ({$user['role']})\n";
echo "Link (válido por " . Auth::PASSWORD_RESET_TTL_HOURS . " horas, uso único):\n";
echo ($baseUrl !== '' ? $baseUrl : '<APP_URL>') . $path . "\n";
