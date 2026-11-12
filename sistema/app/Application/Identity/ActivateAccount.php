<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Transaction;
use App\Domain\Audit\AuditTrail;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\InvalidSecret;
use App\Domain\Shared\NotFound;

/** Sets a password from a one-time link (first access or forgotten password). */
final class ActivateAccount
{
    public function __construct(
        private readonly PasswordResetLinks $links,
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionRepository $sessions,
        private readonly Transaction $transaction,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(string $email, string $token, string $password, ?string $ip): void
    {
        if (!$this->links->isValid($email, $token)) {
            throw new InvalidSecret('Token inválido ou expirado.');
        }
        $user = $this->users->findByEmail($email) ?? throw new NotFound('Usuário não encontrado.');

        $this->transaction->run(function () use ($user, $email, $password): void {
            $now = $this->clock->now();
            $user->passwordHash = $this->hasher->hash($password);
            $user->activate($now);
            $this->users->save($user, $now);
            // The link is single-use, and a new password ends every open session.
            $this->links->consume($email);
            $this->sessions->revokeAllForUser((int) $user->id);
        });

        $this->audit->record('account.password_set', $user->id, ip: $ip);
    }
}
