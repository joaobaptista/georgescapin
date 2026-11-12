<?php

namespace App\Core;

/**
 * Regras de vínculo entre profissional e paciente.
 *
 * Um profissional só enxerga um paciente quando:
 *  - cadastrou o paciente na clínica (patients.professional_id), ou
 *  - o paciente tem agendamento com ele.
 *
 * Criar esse vínculo nunca depende apenas do profissional: o agendamento feito
 * pelo profissional exige vínculo prévio (ver AppointmentController::store),
 * então o vínculo por agendamento nasce de uma solicitação do próprio paciente.
 */
class ClinicalAccess
{
    /** Condição SQL (alias `p` = patients) que restringe pacientes ao profissional. */
    public const PATIENT_SCOPE_SQL = "(p.professional_id = ? OR EXISTS (
            SELECT 1 FROM appointments a WHERE a.patient_id = p.id AND a.professional_id = ?
        ))";

    /** Parâmetros que acompanham PATIENT_SCOPE_SQL. */
    public static function patientScopeParams(int $professionalId): array
    {
        return [$professionalId, $professionalId];
    }

    public static function professionalCanAccessPatient(int $professionalId, int $patientId): bool
    {
        $patient = Database::fetchOne(
            "SELECT p.id FROM patients p WHERE p.id = ? AND " . self::PATIENT_SCOPE_SQL,
            array_merge([$patientId], self::patientScopeParams($professionalId))
        );

        return $patient !== null;
    }
}
