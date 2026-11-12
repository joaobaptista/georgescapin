<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\NotFound;

/** A professional reads a patient's medical record. Every read is audited. */
final class ViewPatientRecords
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly MedicalRecordQueries $records,
        private readonly ClinicalAccess $access,
        private readonly AuditTrail $audit,
    ) {
    }

    /** @return list<array> */
    public function __invoke(Actor $actor, int $patientId): array
    {
        if ($this->patients->find($patientId) === null) {
            throw new NotFound('Paciente não encontrado.');
        }
        if ($actor->professionalId === null || !$this->access->professionalCanAccess($actor->professionalId, $patientId)) {
            throw new AccessDenied('Acesso não autorizado ao prontuário deste paciente.');
        }

        $this->audit->record('medical_records.viewed', $actor->userId, $patientId, 'patient', $patientId, $actor->ip);

        return $this->records->forPatient($patientId);
    }
}
