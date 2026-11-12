<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Clinical\MedicalRecordQueries;
use App\Infrastructure\Persistence\Db;

final class PdoMedicalRecordQueries implements MedicalRecordQueries
{
    private const SELECT = 'SELECT mr.*, a.scheduled_at, a.status AS appointment_status
        FROM medical_records mr LEFT JOIN appointments a ON mr.appointment_id = a.id';

    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $recordId): ?array
    {
        $row = $this->db->one(self::SELECT . ' WHERE mr.id = ?', [$recordId]);

        return $row !== null ? self::view($row) : null;
    }

    public function forPatient(int $patientId): array
    {
        return array_map([self::class, 'view'], $this->db->all(self::SELECT . ' WHERE mr.patient_id = ? ORDER BY mr.created_at DESC, mr.id DESC', [$patientId]));
    }

    private static function view(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'patient_id' => (int) $r['patient_id'],
            'professional_id' => (int) $r['professional_id'],
            'clinical_notes' => $r['clinical_notes'] ?? null,
            'prescription' => $r['prescription'] ?? null,
            'appointment' => $r['appointment_id'] ? [
                'id' => (int) $r['appointment_id'],
                'scheduled_at' => $r['scheduled_at'] ?? null,
                'status' => $r['appointment_status'] ?? null,
            ] : null,
            'created_at' => $r['created_at'],
        ];
    }
}
