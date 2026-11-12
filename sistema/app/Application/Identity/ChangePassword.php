<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Identity\PasswordHasher;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\InvalidSecret;
use App\Domain\Shared\Unauthenticated;

final class ChangePassword
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionRepository $sessions,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(Actor $actor, string $currentPassword, string $newPassword): void
    {
        $user = $this->users->find($actor->userId) ?? throw new Unauthenticated('Token inválido ou expirado.');
        if (!$this->hasher->verify($currentPassword, $user->passwordHash)) {
            throw new InvalidSecret('A senha atual está incorreta.');
        }

        $user->passwordHash = $this->hasher->hash($newPassword);
        $this->users->save($user, $this->clock->now());
        // Other sessions (possibly stolen) end; the current one continues.
        $this->sessions->revokeAllForUser($actor->userId, $actor->sessionTokenHash);
        $this->audit->record('account.password_changed', $actor->userId, ip: $actor->ip);
    }
}
