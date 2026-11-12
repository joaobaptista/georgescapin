<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Clinical\MedicalRecord;
use App\Domain\Clinical\MedicalRecordRepository;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Scheduling\AppointmentRepository;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Clock;
use App\Domain\Shared\NotFound;
use App\Domain\Shared\RuleViolation;

final class AddMedicalRecord
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly AppointmentRepository $appointments,
        private readonly MedicalRecordRepository $records,
        private readonly ClinicalAccess $access,
        private readonly AuditTrail $audit,
        private readonly Clock $clock,
    ) {
    }

    /** @param array{clinical_notes?: ?string, prescription?: ?string, appointment_id?: ?int} $input */
    public function __invoke(Actor $actor, int $patientId, array $input): int
    {
        $professionalId = $actor->professionalIdOrFail('Apenas profissionais de saúde podem criar registros em prontuários.');
        if ($this->patients->find($patientId) === null) {
            throw new NotFound('Paciente não encontrado.');
        }
        if (!$this->access->professionalCanAccess($professionalId, $patientId)) {
            throw new AccessDenied('Acesso não autorizado ao prontuário deste paciente.');
        }

        $appointmentId = !empty($input['appointment_id']) ? (int) $input['appointment_id'] : null;
        if ($appointmentId !== null) {
            $appointment = $this->appointments->find($appointmentId);
            if ($appointment === null || $appointment->patientId !== $patientId || $appointment->professionalId !== $professionalId) {
                throw new RuleViolation('Agendamento inválido para este prontuário.');
            }
        }

        $recordId = $this->records->append(new MedicalRecord(
            $patientId,
            $professionalId,
            $appointmentId,
            $input['clinical_notes'] ?? null,
            $input['prescription'] ?? null,
            $this->clock->now(),
        ));
        $this->audit->record('medical_record.created', $actor->userId, $patientId, 'medical_record', $recordId, $actor->ip);

        return $recordId;
    }
}
