<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/** A login session, identified by the SHA-256 of its bearer token. */
final class Session
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly ?\DateTimeImmutable $lastUsedAt,
    ) {
    }

    /** Valid until the absolute expiry and while used within the idle window. */
    public function isValidAt(\DateTimeImmutable $now, int $idleMinutes): bool
    {
        $lastActivity = $this->lastUsedAt ?? $this->createdAt;

        return $now < $this->expiresAt && $now < $lastActivity->modify("+{$idleMinutes} minutes");
    }
}
