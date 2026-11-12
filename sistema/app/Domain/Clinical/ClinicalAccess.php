<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

/**
 * Who may see a patient's data and medical records.
 *
 * A professional sees only linked patients: registered by them, or with an
 * appointment with them. The appointment link is created by the patient's own
 * booking request; professionals cannot book unlinked patients, so they cannot
 * fabricate a link to reach someone else's records.
 */
final class ClinicalAccess
{
    public function __construct(private readonly PatientRepository $patients)
    {
    }

    public function professionalCanAccess(int $professionalId, int $patientId): bool
    {
        return $this->patients->isLinked($patientId, $professionalId);
    }
}
