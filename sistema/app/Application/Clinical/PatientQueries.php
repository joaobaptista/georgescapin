<?php

declare(strict_types=1);

namespace App\Application\Clinical;

interface PatientQueries
{
    public function find(int $patientId): ?array;

    /** @return list<array> patients linked to the professional */
    public function linkedTo(int $professionalId): array;

    /** @return list<array> */
    public function searchLinkedTo(int $professionalId, string $term): array;
}
