<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\PasswordResetRepository;
use App\Domain\Shared\Clock;

/**
 * One-time links to create or reset a password. There is no e-mail service:
 * the clinic hands the link to the person after confirming their identity.
 */
final class PasswordResetLinks
{
    public const TTL_HOURS = 72;

    public function __construct(
        private readonly PasswordResetRepository $resets,
        private readonly Clock $clock,
    ) {
    }

    /** @return string the path to send, e.g. /ativar-conta?email=...&token=... */
    public function issue(string $email): string
    {
        $token = bin2hex(random_bytes(24));
        $this->resets->replace($email, hash('sha256', $token), $this->clock->now());

        return '/ativar-conta?' . http_build_query(['email' => $email, 'token' => $token]);
    }

    public function isValid(string $email, string $token): bool
    {
        $issuedAt = $this->resets->issuedAt($email, hash('sha256', $token));
        if ($issuedAt === null) {
            return false;
        }
        if ($this->clock->now() > $issuedAt->modify('+' . self::TTL_HOURS . ' hours')) {
            $this->resets->forget($email);
            return false;
        }

        return true;
    }

    public function consume(string $email): void
    {
        $this->resets->forget($email);
    }
}
