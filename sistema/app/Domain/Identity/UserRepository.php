<?php

declare(strict_types=1);

namespace App\Domain\Identity;

interface UserRepository
{
    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByGoogleSubject(string $subject): ?User;

    public function emailInUse(string $email, ?int $exceptUserId = null): bool;

    public function save(User $user, \DateTimeImmutable $now): int;
}
