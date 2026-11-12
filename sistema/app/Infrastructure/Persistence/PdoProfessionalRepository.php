<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Clinical\ProfessionalRepository;

final class PdoProfessionalRepository implements ProfessionalRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function exists(int $id): bool
    {
        return $this->db->one('SELECT id FROM professionals WHERE id = ?', [$id]) !== null;
    }

    public function findIdByUser(int $userId): ?int
    {
        $row = $this->db->one('SELECT id FROM professionals WHERE user_id = ?', [$userId]);

        return $row !== null ? (int) $row['id'] : null;
    }

    public function add(int $userId, string $specialty, ?string $crm, ?string $city, ?string $state, \DateTimeImmutable $now): int
    {
        return $this->db->insert(
            'INSERT INTO professionals (user_id, specialty, crm, address_city, address_state, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $specialty, $crm, $city, $state, Db::ts($now), Db::ts($now)],
        );
    }
}
