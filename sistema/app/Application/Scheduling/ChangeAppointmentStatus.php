<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Application\Shared\Actor;
use App\Domain\Scheduling\Agenda;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Scheduling\AppointmentStatus;
use App\Domain\Shared\Clock;
use App\Domain\Shared\NotFound;

final class ChangeAppointmentStatus
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly Agenda $agenda,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(Actor $actor, int $appointmentId, AppointmentStatus $next): void
    {
        $appointment = $this->appointments->find($appointmentId) ?? throw new NotFound('Agendamento não encontrado.');
        $party = $appointment->partyOf($actor->patientId, $actor->professionalId);
        $wasFree = !$appointment->status()->occupiesSlot();

        $appointment->changeStatus($next, $party);

        // Reactivating a canceled appointment takes its slot back.
        if ($wasFree && $next->occupiesSlot()) {
            $this->agenda->assertFree(
                $appointment->professionalId,
                $this->agenda->slotFor($appointment->scheduledAt(), $appointment->procedureId()),
                $appointmentId,
            );
        }

        $this->appointments->save($appointment, $this->clock->now());
    }
}
