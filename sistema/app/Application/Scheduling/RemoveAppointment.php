<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Scheduling\AppointmentRepository;

final class RemoveAppointment
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly AuditTrail $audit,
    ) {
    }

    /** Idempotent: removing an appointment that no longer exists succeeds. */
    public function __invoke(Actor $actor, int $appointmentId): void
    {
        $appointment = $this->appointments->find($appointmentId);
        if ($appointment === null) {
            return;
        }

        $appointment->assertRemovableBy($appointment->partyOf($actor->patientId, $actor->professionalId));
        $this->appointments->remove($appointmentId);
        $this->audit->record('appointment.deleted', $actor->userId, $appointment->patientId, 'appointment', $appointmentId, $actor->ip);
    }
}
