<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Clinical\Patient;
use App\Domain\Clinical\PatientRepository;

final class PdoPatientRepository implements PatientRepository
{
    /** Patients linked to a professional (alias p = patients); see ClinicalAccess. */
    public const LINKED_TO_PROFESSIONAL = '(p.professional_id = ? OR EXISTS (
            SELECT 1 FROM appointments a WHERE a.patient_id = p.id AND a.professional_id = ?
        ))';

    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?Patient
    {
        return $this->map($this->db->one('SELECT * FROM patients WHERE id = ?', [$id]));
    }

    public function findByUser(int $userId): ?Patient
    {
        return $this->map($this->db->one('SELECT * FROM patients WHERE user_id = ?', [$userId]));
    }

    public function save(Patient $patient, \DateTimeImmutable $now): int
    {
        if ($patient->id === null) {
            return $this->db->insert(
                'INSERT INTO patients (user_id, professional_id, cpf, phone, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$patient->userId, $patient->registeredBy, $patient->cpf, $patient->phone, $patient->birthDate, Db::ts($now), Db::ts($now)],
            );
        }

        $this->db->execute(
            'UPDATE patients SET cpf = ?, phone = ?, birth_date = ?, updated_at = ? WHERE id = ?',
            [$patient->cpf, $patient->phone, $patient->birthDate, Db::ts($now), $patient->id],
        );

        return $patient->id;
    }

    public function isLinked(int $patientId, int $professionalId): bool
    {
        return $this->db->one(
            'SELECT p.id FROM patients p WHERE p.id = ? AND ' . self::LINKED_TO_PROFESSIONAL,
            [$patientId, $professionalId, $professionalId],
        ) !== null;
    }

    private function map(?array $row): ?Patient
    {
        if ($row === null) {
            return null;
        }

        return new Patient(
            (int) $row['id'],
            $row['user_id'] !== null ? (int) $row['user_id'] : null,
            $row['professional_id'] !== null ? (int) $row['professional_id'] : null,
            $row['cpf'],
            $row['phone'],
            $row['birth_date'],
        );
    }
}
