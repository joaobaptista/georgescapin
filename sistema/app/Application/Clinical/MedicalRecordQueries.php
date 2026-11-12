<?php

declare(strict_types=1);

namespace App\Application\Clinical;

interface MedicalRecordQueries
{
    public function find(int $recordId): ?array;

    /** @return list<array> newest first */
    public function forPatient(int $patientId): array;
}
