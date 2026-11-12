<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Shared\Conflict;

/** Domain service: a professional attends one patient at a time. */
final class Agenda
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly ProcedureRepository $procedures,
    ) {
    }

    public function slotFor(\DateTimeImmutable $start, ?int $procedureId): TimeSlot
    {
        $minutes = $procedureId !== null ? $this->procedures->find($procedureId)?->durationMinutes : null;

        return TimeSlot::starting($start, $minutes);
    }

    public function assertFree(int $professionalId, TimeSlot $slot, ?int $ignoreAppointmentId = null): void
    {
        // Only appointments starting up to one day earlier can reach this slot.
        $busy = $this->appointments->busySlots($professionalId, $slot->start->modify('-1 day'), $slot->end, $ignoreAppointmentId);

        foreach ($busy as $other) {
            if ($slot->overlaps($other)) {
                $window = $other->label();
                throw new Conflict("Horário indisponível: já existe um atendimento em {$window}.", [
                    'scheduled_at' => ["Conflita com outro atendimento ({$window})."],
                ]);
            }
        }
    }
}
