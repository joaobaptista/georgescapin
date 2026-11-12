<?php

declare(strict_types=1);

namespace App\Application\Catalog;

interface ProcedureQueries
{
    public function find(int $id): ?array;

    /** @return list<array> the professional's own catalog, by name */
    public function ownedBy(int $professionalId): array;
}
