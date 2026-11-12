<?php

declare(strict_types=1);

/**
 * Cria uma conta de profissional de saúde (uso exclusivo da clínica).
 *
 *   php scripts/create-professional.php --name="Dra. Ana Souza" --email=ana@clinica.com.br \
 *       [--specialty="Dermatologia"] [--crm="CRM/SP 000000"] [--city="São Paulo"] [--state=SP]
 *
 * Senha: variável NUVA_PRO_PASSWORD (mínimo 8). Sem ela, uma senha aleatória é
 * gerada e exibida uma única vez.
 */

use App\Application\Identity\ProvisionProfessional;
use App\Domain\Shared\DomainError;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$container = require dirname(__DIR__) . '/bootstrap/app.php';

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

try {
    $ids = $container->get(ProvisionProfessional::class)(
        $name,
        $email,
        $password,
        $options['specialty'] ?? 'Clínico Geral',
        $options['crm'] ?? null,
        $options['city'] ?? null,
        $options['state'] ?? null,
    );
} catch (DomainError $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}

echo "Profissional criado (usuário #{$ids['userId']}, profissional #{$ids['professionalId']}): {$email}\n";
if ($generated) {
    echo "Senha gerada (anote agora, ela não será exibida novamente): {$password}\n";
}
