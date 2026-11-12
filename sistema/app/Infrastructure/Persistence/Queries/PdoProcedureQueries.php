<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Catalog\ProcedureQueries;
use App\Infrastructure\Persistence\Db;

final class PdoProcedureQueries implements ProcedureQueries
{
    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?array
    {
        $row = $this->db->one('SELECT * FROM procedures WHERE id = ?', [$id]);

        return $row !== null ? self::view($row) : null;
    }

    public function ownedBy(int $professionalId): array
    {
        return array_map([self::class, 'view'], $this->db->all('SELECT * FROM procedures WHERE professional_id = ? ORDER BY name ASC', [$professionalId]));
    }

    private static function view(array $p): array
    {
        return [
            'id' => (int) $p['id'],
            'professional_id' => (int) $p['professional_id'],
            'name' => $p['name'],
            'category' => $p['category'] ?? null,
            'description' => $p['description'] ?? null,
            'base_price' => (float) ($p['base_price'] ?? 0),
            'duration_minutes' => (int) ($p['duration_minutes'] ?? 30),
            'created_at' => $p['created_at'],
        ];
    }
}
