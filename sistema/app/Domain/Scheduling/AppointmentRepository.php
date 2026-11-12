<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

interface AppointmentRepository
{
    public function find(int $id): ?Appointment;

    /** Inserts or updates; returns the appointment id. */
    public function save(Appointment $appointment, \DateTimeImmutable $now): int;

    public function remove(int $id): void;

    /**
     * Slots kept busy by the professional's appointments that start in
     * [$from, $until), excluding canceled ones and $ignoreId.
     *
     * @return list<TimeSlot>
     */
    public function busySlots(int $professionalId, \DateTimeImmutable $from, \DateTimeImmutable $until, ?int $ignoreId): array;
}
