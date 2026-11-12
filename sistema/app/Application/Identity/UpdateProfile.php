<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Actor;
use App\Application\Shared\Transaction;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\InvalidSecret;
use App\Domain\Shared\RuleViolation;
use App\Domain\Shared\Unauthenticated;

final class UpdateProfile
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly PasswordHasher $hasher,
        private readonly SessionRepository $sessions,
        private readonly Transaction $transaction,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    /** @param array{name: string, email: string, current_password?: ?string, phone?: ?string, cpf?: ?string, birth_date?: ?string} $input */
    public function __invoke(Actor $actor, array $input): void
    {
        $user = $this->users->find($actor->userId) ?? throw new Unauthenticated('Token inválido ou expirado.');
        $emailChanged = strcasecmp($user->email, $input['email']) !== 0;

        if ($this->users->emailInUse($input['email'], $actor->userId)) {
            throw new RuleViolation('Este e-mail já está em uso.', ['email' => ['Este e-mail já está em uso.']]);
        }
        // The e-mail is the login: changing it with a stolen session would
        // lock the owner out, so it requires the current password.
        if ($emailChanged) {
            $current = (string) ($input['current_password'] ?? '');
            if ($current === '') {
                throw new RuleViolation('Confirme sua senha atual para alterar o e-mail.', [
                    'current_password' => ['Informe a senha atual para alterar o e-mail de acesso.'],
                ]);
            }
            if (!$this->hasher->verify($current, $user->passwordHash)) {
                throw new InvalidSecret('A senha atual está incorreta.');
            }
        }

        $this->transaction->run(function () use ($actor, $user, $input, $emailChanged): void {
            $now = $this->clock->now();
            $user->name = $input['name'];
            $user->email = $input['email'];
            $this->users->save($user, $now);

            $patient = $actor->patientId !== null ? $this->patients->find($actor->patientId) : null;
            if ($patient !== null) {
                $patient->phone = $input['phone'] ?? $patient->phone;
                $patient->cpf = $input['cpf'] ?? $patient->cpf;
                $patient->birthDate = $input['birth_date'] ?? $patient->birthDate;
                $this->patients->save($patient, $now);
            }
            if ($emailChanged) {
                $this->sessions->revokeAllForUser($actor->userId, $actor->sessionTokenHash);
            }
        });

        if ($emailChanged) {
            $this->audit->record('account.email_changed', $actor->userId, ip: $actor->ip);
        }
    }
}
