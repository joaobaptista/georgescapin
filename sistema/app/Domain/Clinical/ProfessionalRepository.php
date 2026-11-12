<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

interface ProfessionalRepository
{
    public function exists(int $id): bool;

    public function findIdByUser(int $userId): ?int;

    public function add(int $userId, string $specialty, ?string $crm, ?string $city, ?string $state, \DateTimeImmutable $now): int;
}
