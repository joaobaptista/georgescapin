<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\SessionRepository;
use App\Domain\Shared\Clock;

final class SessionIssuer
{
    public function __construct(
        private readonly SessionRepository $sessions,
        private readonly SessionPolicy $policy,
        private readonly Clock $clock,
    ) {
    }

    /** Returns the plain bearer token; only its SHA-256 is stored. */
    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $now = $this->clock->now();
        $this->sessions->create($userId, hash('sha256', $token), $now, $now->modify("+{$this->policy->lifetimeHours} hours"));

        return $token;
    }
}
