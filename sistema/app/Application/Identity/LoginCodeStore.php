<?php

declare(strict_types=1);

namespace App\Application\Identity;

/**
 * One-time codes that hand a completed external sign-in to the browser. The
 * session token itself never travels in a URL.
 */
interface LoginCodeStore
{
    public function put(string $codeHash, int $userId, \DateTimeImmutable $createdAt): void;

    /** @return array{userId: int, createdAt: \DateTimeImmutable}|null deleted on read (single use) */
    public function take(string $codeHash): ?array;
}
