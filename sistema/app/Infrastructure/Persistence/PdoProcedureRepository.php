<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Catalog\Procedure;
use App\Domain\Catalog\ProcedureRepository;

final class PdoProcedureRepository implements ProcedureRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?Procedure
    {
        $row = $this->db->one('SELECT * FROM procedures WHERE id = ?', [$id]);
        if ($row === null) {
            return null;
        }

        return new Procedure(
            (int) $row['id'],
            (int) $row['professional_id'],
            (string) $row['name'],
            $row['category'],
            $row['description'],
            (float) ($row['base_price'] ?? 0),
            (int) ($row['duration_minutes'] ?? 30),
        );
    }

    public function save(Procedure $procedure, \DateTimeImmutable $now): int
    {
        $fields = [$procedure->name, $procedure->category, $procedure->description, $procedure->basePrice, $procedure->durationMinutes];

        if ($procedure->id === null) {
            return $this->db->insert(
                'INSERT INTO procedures (professional_id, name, category, description, base_price, duration_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$procedure->professionalId, ...$fields, Db::ts($now), Db::ts($now)],
            );
        }

        $this->db->execute(
            'UPDATE procedures SET name = ?, category = ?, description = ?, base_price = ?, duration_minutes = ?, updated_at = ? WHERE id = ?',
            [...$fields, Db::ts($now), $procedure->id],
        );

        return $procedure->id;
    }

    public function remove(int $id): void
    {
        $this->db->execute('DELETE FROM procedures WHERE id = ?', [$id]);
    }
}
