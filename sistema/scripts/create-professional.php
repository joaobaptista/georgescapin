<?php

declare(strict_types=1);

/**
 * Cria uma conta de profissional de saúde no banco configurado em .env.
 *
 * Profissionais têm acesso a dados clínicos, por isso não são criados pelo
 * cadastro público do site.
 *
 * Uso:
 *   php scripts/create-professional.php --name="Dra. Ana Souza" --email=ana@clinica.com.br \
 *       [--specialty="Dermatologia"] [--crm="CRM/SP 000000"] [--city="São Paulo"] [--state=SP]
 *
 * A senha é lida da variável NUVA_PRO_PASSWORD (mínimo de 8 caracteres). Sem ela,
 * uma senha aleatória é gerada e exibida uma única vez.
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

$options = getopt('', ['name:', 'email:', 'specialty:', 'crm:', 'city:', 'state:']);
$name = trim((string) ($options['name'] ?? ''));
$email = trim((string) ($options['email'] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Informe --name e um --email válido.\n");
    exit(2);
}

$password = (string) getenv('NUVA_PRO_PASSWORD');
$generated = $password === '';
if ($generated) {
    $password = bin2hex(random_bytes(8));
} elseif (mb_strlen($password) < 8) {
    fwrite(STDERR, "NUVA_PRO_PASSWORD deve ter pelo menos 8 caracteres.\n");
    exit(2);
}

if (Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email])) {
    fwrite(STDERR, "Já existe um usuário com o e-mail {$email}.\n");
    exit(1);
}

$db = Database::getConnection();
$db->beginTransaction();
try {
    $userId = Database::insert(
        "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'professional', datetime('now'), datetime('now'))",
        [$name, $email, Auth::hashPassword($password)]
    );
    $professionalId = Database::insert(
        "INSERT INTO professionals (user_id, specialty, crm, address_city, address_state, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
        [
            $userId,
            $options['specialty'] ?? 'Clínico Geral',
            $options['crm'] ?? null,
            $options['city'] ?? null,
            $options['state'] ?? null,
        ]
    );
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, "Não foi possível criar o profissional: {$error->getMessage()}\n");
    exit(1);
}

echo "Profissional criado (usuário #{$userId}, profissional #{$professionalId}): {$email}\n";
if ($generated) {
    echo "Senha gerada (anote agora, ela não será exibida novamente): {$password}\n";
}
