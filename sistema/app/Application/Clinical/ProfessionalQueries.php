<?php

declare(strict_types=1);

namespace App\Application\Clinical;

interface ProfessionalQueries
{
    public function dashboard(int $professionalId): ?array;

    /** @return list<array> public directory search */
    public function search(string $term): array;
}
