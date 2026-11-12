<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Actor;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Clinical\ProfessionalRepository;
use App\Domain\Identity\SessionRepository;
use App\Domain\Identity\UserRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\Unauthenticated;

final class AuthenticateSession
{
    /** Avoids a write on every request: activity is recorded at most once a minute. */
    private const TOUCH_INTERVAL_SECONDS = 60;

    public function __construct(
        private readonly SessionRepository $sessions,
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly ProfessionalRepository $professionals,
        private readonly SessionPolicy $policy,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(string $bearerToken, ?string $ip): Actor
    {
        $tokenHash = hash('sha256', $bearerToken);
        $session = $this->sessions->findByTokenHash($tokenHash);
        $now = $this->clock->now();

        if ($session === null || !$session->isValidAt($now, $this->policy->idleMinutes)) {
            if ($session !== null) {
                $this->sessions->revoke($tokenHash);
            }
            throw new Unauthenticated('Token inválido ou expirado.');
        }

        $user = $this->users->find($session->userId) ?? throw new Unauthenticated('Token inválido ou expirado.');

        $lastActivity = $session->lastUsedAt ?? $session->createdAt;
        if ($now->getTimestamp() - $lastActivity->getTimestamp() >= self::TOUCH_INTERVAL_SECONDS) {
            $this->sessions->touch($session->id, $now);
        }

        return new Actor(
            (int) $user->id,
            $user->role,
            $this->patients->findByUser((int) $user->id)?->id,
            $this->professionals->findIdByUser((int) $user->id),
            $tokenHash,
            $ip,
        );
    }
}
