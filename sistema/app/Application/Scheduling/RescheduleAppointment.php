<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Application\Shared\Actor;
use App\Application\Shared\LocalDateTime;
use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Scheduling\Agenda;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Shared\Clock;
use App\Domain\Shared\NotFound;

final class RescheduleAppointment
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly ProcedureRepository $procedures,
        private readonly Agenda $agenda,
        private readonly LocalDateTime $dates,
        private readonly Clock $clock,
    ) {
    }

    /** @param array{procedure_id?: ?int, scheduled_at: string, notes?: ?string} $input */
    public function __invoke(Actor $actor, int $appointmentId, array $input): void
    {
        $appointment = $this->appointments->find($appointmentId) ?? throw new NotFound('Agendamento não encontrado.');
        $party = $appointment->partyOf($actor->patientId, $actor->professionalId);

        $scheduledAt = $this->dates->parse($input['scheduled_at']);
        $procedureId = ($input['procedure_id'] ?? null) !== null
            ? ProcedureGuard::ownedBy($this->procedures, $input['procedure_id'], $appointment->professionalId)
            : $appointment->procedureId();

        $appointment->reschedule($scheduledAt, $procedureId, $input['notes'] ?? $appointment->notes(), $party, $this->clock->now());
        $this->agenda->assertFree($appointment->professionalId, $this->agenda->slotFor($scheduledAt, $procedureId), $appointmentId);

        $this->appointments->save($appointment, $this->clock->now());
    }
}
