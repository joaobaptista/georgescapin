<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Clinical\MedicalRecord;
use App\Domain\Clinical\MedicalRecordRepository;

final class PdoMedicalRecordRepository implements MedicalRecordRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function append(MedicalRecord $record): int
    {
        return $this->db->insert(
            'INSERT INTO medical_records (patient_id, professional_id, appointment_id, clinical_notes, prescription, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $record->patientId,
                $record->professionalId,
                $record->appointmentId,
                $record->clinicalNotes,
                $record->prescription,
                Db::ts($record->createdAt),
                Db::ts($record->createdAt),
            ],
        );
    }
}
