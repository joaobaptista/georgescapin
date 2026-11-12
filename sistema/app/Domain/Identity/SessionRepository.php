<?php

declare(strict_types=1);

namespace App\Domain\Identity;

interface SessionRepository
{
    public function create(int $userId, string $tokenHash, \DateTimeImmutable $createdAt, \DateTimeImmutable $expiresAt): void;

    public function findByTokenHash(string $tokenHash): ?Session;

    public function touch(int $sessionId, \DateTimeImmutable $at): void;

    public function revoke(string $tokenHash): void;

    /** Ends every session of the user, optionally keeping the current one. */
    public function revokeAllForUser(int $userId, ?string $exceptTokenHash = null): void;
}
