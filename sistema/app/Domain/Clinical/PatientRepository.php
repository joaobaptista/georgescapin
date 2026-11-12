<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

interface PatientRepository
{
    public function find(int $id): ?Patient;

    public function findByUser(int $userId): ?Patient;

    public function save(Patient $patient, \DateTimeImmutable $now): int;

    /**
     * A professional is linked to a patient they registered or who has an
     * appointment with them. See ClinicalAccess.
     */
    public function isLinked(int $patientId, int $professionalId): bool;
}
