<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Transaction;
use App\Domain\Clinical\Patient;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

/** Public sign-up. Creates patient accounts only. */
final class RegisterPatientAccount
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly PasswordHasher $hasher,
        private readonly SessionIssuer $sessions,
        private readonly Transaction $transaction,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @param array{name: string, email: string, password: string, role?: ?string, cpf?: ?string, phone?: ?string} $input
     * @return array{token: string, userId: int}
     */
    public function __invoke(array $input): array
    {
        if ($this->users->emailInUse($input['email'])) {
            throw new RuleViolation('Este e-mail já está em uso.', ['email' => ['Este e-mail já está em uso.']]);
        }
        // Professional accounts reach clinical data: the clinic creates them
        // (scripts/create-professional.php), never the public sign-up.
        if (($input['role'] ?? 'patient') !== Role::Patient->value) {
            throw new AccessDenied('O cadastro de profissionais é feito pela clínica. Solicite seu acesso à administração.');
        }

        $now = $this->clock->now();
        $userId = $this->transaction->run(function () use ($input, $now): int {
            $user = new User(null, $input['name'], $input['email'], $this->hasher->hash($input['password']), Role::Patient, $now);
            $userId = $this->users->save($user, $now);
            $this->patients->save(new Patient(null, $userId, null, $input['cpf'] ?? null, $input['phone'] ?? null, null), $now);

            return $userId;
        });

        return ['token' => $this->sessions->issue($userId), 'userId' => $userId];
    }
}
