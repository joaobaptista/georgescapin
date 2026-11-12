<?php

declare(strict_types=1);

namespace App\Application\Clinical;

use App\Application\Shared\Actor;
use App\Domain\Audit\AuditTrail;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Clinical\PatientRepository;
use App\Domain\Identity\Role;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\NotFound;

final class ShowPatient
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly PatientQueries $queries,
        private readonly ClinicalAccess $access,
        private readonly AuditTrail $audit,
    ) {
    }

    public function __invoke(Actor $actor, int $patientId): array
    {
        if ($actor->role === Role::Patient && $actor->patientId !== $patientId) {
            throw new AccessDenied('Acesso não autorizado aos dados deste paciente.');
        }
        $patient = $this->patients->find($patientId) ?? throw new NotFound('Paciente não encontrado.');

        $professionalId = $actor->professionalId;
        if ($actor->role === Role::Professional) {
            if ($professionalId === null || !$this->access->professionalCanAccess($professionalId, $patientId)) {
                throw new AccessDenied('Acesso não autorizado aos dados deste paciente.');
            }
            $this->audit->record('patient.viewed', $actor->userId, $patientId, 'patient', $patientId, $actor->ip);
        }

        $view = $this->queries->find($patientId);
        // Only the professional who registered the patient may issue the activation link.
        $view['can_generate_activation_link'] = $view['portal_status'] === 'pending'
            && $professionalId !== null
            && $patient->wasRegisteredBy($professionalId);

        return $view;
    }
}
