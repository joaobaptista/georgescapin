<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Application\Shared\Actor;
use App\Application\Shared\LocalDateTime;
use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Clinical\ProfessionalRepository;
use App\Domain\Identity\Role;
use App\Domain\Scheduling\Agenda;
use App\Domain\Scheduling\Appointment;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Scheduling\Party;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

final class ScheduleAppointment
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly ProcedureRepository $procedures,
        private readonly ProfessionalRepository $professionals,
        private readonly ClinicalAccess $access,
        private readonly Agenda $agenda,
        private readonly LocalDateTime $dates,
        private readonly Clock $clock,
    ) {
    }

    /** @param array{patient_id?: int, professional_id?: int, procedure_id?: ?int, scheduled_at: string, notes?: ?string} $input */
    public function __invoke(Actor $actor, array $input): int
    {
        $byProfessional = $actor->role === Role::Professional;
        $professionalId = $byProfessional ? $actor->professionalId : (int) $input['professional_id'];
        $patientId = $byProfessional ? (int) $input['patient_id'] : $actor->patientId;

        if (!$professionalId || !$patientId) {
            throw new RuleViolation('Perfil do usuário incompleto para realizar o agendamento.');
        }
        if ($byProfessional) {
            // Booking links the patient to the professional (and to their
            // records), so a professional may only book patients already linked.
            if (!$this->access->professionalCanAccess($professionalId, $patientId)) {
                throw new AccessDenied('Paciente não encontrado entre os seus pacientes.');
            }
        } elseif (!$this->professionals->exists($professionalId)) {
            throw new RuleViolation('Profissional não encontrado.', ['professional_id' => ['Profissional não encontrado.']]);
        }

        $scheduledAt = $this->dates->parse($input['scheduled_at']);
        $procedureId = ProcedureGuard::ownedBy($this->procedures, $input['procedure_id'] ?? null, $professionalId);

        $appointment = Appointment::book(
            $patientId,
            $professionalId,
            $procedureId,
            $scheduledAt,
            $input['notes'] ?? null,
            $byProfessional ? Party::Professional : Party::Patient,
            $this->clock->now(),
        );
        $this->agenda->assertFree($professionalId, $this->agenda->slotFor($scheduledAt, $procedureId));

        return $this->appointments->save($appointment, $this->clock->now());
    }
}
