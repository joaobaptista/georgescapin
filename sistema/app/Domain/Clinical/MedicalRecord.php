<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

/**
 * An entry in the patient's medical record. Entries are append-only: there is
 * no edit or delete, which keeps the clinical history trustworthy.
 */
final class MedicalRecord
{
    public function __construct(
        public readonly int $patientId,
        public readonly int $professionalId,
        public readonly ?int $appointmentId,
        public readonly ?string $clinicalNotes,
        public readonly ?string $prescription,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
