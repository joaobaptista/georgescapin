<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Scheduling\AppointmentQueries;
use App\Infrastructure\Persistence\Db;

final class PdoAppointmentQueries implements AppointmentQueries
{
    private const SELECT = 'SELECT a.*,
               pat.phone AS patient_phone,
               pu.name AS patient_name,
               proc.name AS procedure_name,
               prou.name AS professional_name
        FROM appointments a
        LEFT JOIN patients pat ON a.patient_id = pat.id
        LEFT JOIN users pu ON pat.user_id = pu.id
        LEFT JOIN procedures proc ON a.procedure_id = proc.id
        LEFT JOIN professionals pro ON a.professional_id = pro.id
        LEFT JOIN users prou ON pro.user_id = prou.id';

    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?array
    {
        $row = $this->db->one(self::SELECT . ' WHERE a.id = ?', [$id]);

        return $row !== null ? self::view($row) : null;
    }

    public function forProfessional(int $professionalId): array
    {
        return array_map([self::class, 'view'], $this->db->all(self::SELECT . ' WHERE a.professional_id = ? ORDER BY a.scheduled_at ASC', [$professionalId]));
    }

    public function forPatient(int $patientId): array
    {
        return array_map([self::class, 'view'], $this->db->all(self::SELECT . ' WHERE a.patient_id = ? ORDER BY a.scheduled_at ASC', [$patientId]));
    }

    private static function view(array $a): array
    {
        return [
            'id' => (int) $a['id'],
            'scheduled_at' => $a['scheduled_at'],
            'status' => $a['status'],
            'notes' => $a['notes'],
            'patient_id' => (int) $a['patient_id'],
            'patient' => ['id' => (int) $a['patient_id'], 'name' => $a['patient_name'] ?? null, 'phone' => $a['patient_phone'] ?? null],
            'procedure_id' => $a['procedure_id'] ? (int) $a['procedure_id'] : null,
            'procedure' => $a['procedure_name'] ? ['id' => (int) $a['procedure_id'], 'name' => $a['procedure_name']] : null,
            'professional_id' => (int) $a['professional_id'],
            'professional' => ['id' => (int) $a['professional_id'], 'name' => $a['professional_name'] ?? null],
            'created_at' => $a['created_at'],
        ];
    }
}
