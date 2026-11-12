<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/** Login account. A patient account registered by the clinic starts pending. */
final class User
{
    public function __construct(
        public readonly ?int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public readonly Role $role,
        public ?\DateTimeImmutable $activatedAt,
        /** Stable Google account id ("sub"), once the user signs in with Google. */
        public ?string $googleSubject = null,
    ) {
    }

    public function isActive(): bool
    {
        return $this->activatedAt !== null;
    }

    public function activate(\DateTimeImmutable $now): void
    {
        $this->activatedAt ??= $now;
    }

    public function linkGoogle(string $subject): void
    {
        $this->googleSubject = $subject;
    }
}
