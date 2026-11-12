<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/** One-time links to create or reset a password (stored as SHA-256). */
interface PasswordResetRepository
{
    public function replace(string $email, string $tokenHash, \DateTimeImmutable $createdAt): void;

    public function issuedAt(string $email, string $tokenHash): ?\DateTimeImmutable;

    public function forget(string $email): void;
}
