<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Transaction;
use App\Domain\Clinical\ProfessionalRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

/** Clinic-side creation of professional accounts (CLI only). */
final class ProvisionProfessional
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ProfessionalRepository $professionals,
        private readonly PasswordHasher $hasher,
        private readonly Transaction $transaction,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{userId: int, professionalId: int} */
    public function __invoke(string $name, string $email, string $password, string $specialty, ?string $crm, ?string $city, ?string $state): array
    {
        if ($this->users->emailInUse($email)) {
            throw new RuleViolation("Já existe um usuário com o e-mail {$email}.");
        }

        return $this->transaction->run(function () use ($name, $email, $password, $specialty, $crm, $city, $state): array {
            $now = $this->clock->now();
            $userId = $this->users->save(new User(null, $name, $email, $this->hasher->hash($password), Role::Professional, $now), $now);

            return ['userId' => $userId, 'professionalId' => $this->professionals->add($userId, $specialty, $crm, $city, $state, $now)];
        });
    }
}
