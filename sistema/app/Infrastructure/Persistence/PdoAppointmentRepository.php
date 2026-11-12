<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Scheduling\Appointment;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Scheduling\AppointmentStatus;
use App\Domain\Scheduling\TimeSlot;

final class PdoAppointmentRepository implements AppointmentRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?Appointment
    {
        $row = $this->db->one('SELECT * FROM appointments WHERE id = ?', [$id]);
        if ($row === null) {
            return null;
        }

        return Appointment::restore(
            (int) $row['id'],
            (int) $row['patient_id'],
            (int) $row['professional_id'],
            $row['procedure_id'] !== null ? (int) $row['procedure_id'] : null,
            new \DateTimeImmutable((string) $row['scheduled_at']),
            AppointmentStatus::from((string) $row['status']),
            $row['notes'],
        );
    }

    public function save(Appointment $appointment, \DateTimeImmutable $now): int
    {
        if ($appointment->id === null) {
            return $this->db->insert(
                'INSERT INTO appointments (patient_id, professional_id, procedure_id, scheduled_at, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $appointment->patientId,
                    $appointment->professionalId,
                    $appointment->procedureId(),
                    Db::ts($appointment->scheduledAt()),
                    $appointment->status()->value,
                    $appointment->notes(),
                    Db::ts($now),
                    Db::ts($now),
                ],
            );
        }

        $this->db->execute(
            'UPDATE appointments SET procedure_id = ?, scheduled_at = ?, status = ?, notes = ?, updated_at = ? WHERE id = ?',
            [
                $appointment->procedureId(),
                Db::ts($appointment->scheduledAt()),
                $appointment->status()->value,
                $appointment->notes(),
                Db::ts($now),
                $appointment->id,
            ],
        );

        return $appointment->id;
    }

    public function remove(int $id): void
    {
        $this->db->execute('DELETE FROM appointments WHERE id = ?', [$id]);
    }

    public function busySlots(int $professionalId, \DateTimeImmutable $from, \DateTimeImmutable $until, ?int $ignoreId): array
    {
        $rows = $this->db->all(
            "SELECT a.scheduled_at, proc.duration_minutes
             FROM appointments a
             LEFT JOIN procedures proc ON a.procedure_id = proc.id
             WHERE a.professional_id = ? AND a.status <> ? AND a.id <> ?
               AND a.scheduled_at >= ? AND a.scheduled_at < ?",
            [$professionalId, AppointmentStatus::Canceled->value, $ignoreId ?? 0, Db::ts($from), Db::ts($until)],
        );

        $slots = [];
        foreach ($rows as $row) {
            try {
                $start = new \DateTimeImmutable((string) $row['scheduled_at']);
            } catch (\Exception) {
                continue; // legacy row with an unreadable date does not block the agenda
            }
            $slots[] = TimeSlot::starting($start, $row['duration_minutes'] !== null ? (int) $row['duration_minutes'] : null);
        }

        return $slots;
    }
}
