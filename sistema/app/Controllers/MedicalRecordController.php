<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class MedicalRecordController
{
    public function recordsForPatient(Request $request): void
    {
        $patientId = (int) $request->param('id');
        $userRole = $request->user['role'] ?? '';
        $userPatientId = $request->user['patient']['id'] ?? null;
        $userProfessionalId = $request->user['professional']['id'] ?? null;

        // Se for paciente, só pode consultar seu próprio prontuário
        if ($userRole === 'patient' && $userPatientId !== $patientId) {
            Response::json(['message' => 'Acesso não autorizado ao prontuário deste paciente.'], 403);
        }

        // Acesso profissional exige vínculo explícito (cadastro na clínica ou
        // agendamento). Isso impede consulta direta a prontuários de terceiros.
        if ($userRole === 'professional') {
            $patient = Database::fetchOne("SELECT id FROM patients WHERE id = ?", [$patientId]);
            if (!$patient) {
                Response::json(['message' => 'Paciente não encontrado.'], 404);
            }

            if (!$userProfessionalId || !$this->professionalCanAccessPatient((int) $userProfessionalId, $patientId)) {
                Response::json(['message' => 'Acesso não autorizado ao prontuário deste paciente.'], 403);
            }
        }

        $sql = "SELECT mr.*, a.scheduled_at, a.status as appointment_status
                FROM medical_records mr
                LEFT JOIN appointments a ON mr.appointment_id = a.id
                WHERE mr.patient_id = ?
                ORDER BY mr.created_at DESC";
        $records = Database::fetchAll($sql, [$patientId]);

        $formatted = array_map([$this, 'formatRecord'], $records);
        Response::json(['data' => $formatted]);
    }

    public function storeRecord(Request $request): void
    {
        $patientId = (int) $request->param('id');
        $professionalId = $request->user['professional']['id'] ?? null;

        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde podem criar registros em prontuários.'], 403);
        }

        $patient = Database::fetchOne("SELECT id FROM patients WHERE id = ?", [$patientId]);
        if (!$patient) {
            Response::json(['message' => 'Paciente não encontrado.'], 404);
        }

        if (!$this->professionalCanAccessPatient((int) $professionalId, $patientId)) {
            Response::json(['message' => 'Acesso não autorizado ao prontuário deste paciente.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'clinical_notes' => 'nullable|string',
            'prescription'   => 'nullable|string',
            'appointment_id' => 'nullable|integer',
        ]);

        $validated = $validator->validated();

        if (!empty($validated['appointment_id'])) {
            $appointment = Database::fetchOne(
                "SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND professional_id = ?",
                [(int) $validated['appointment_id'], $patientId, (int) $professionalId]
            );
            if (!$appointment) {
                Response::json(['message' => 'Agendamento inválido para este prontuário.'], 422);
            }
        }

        $id = Database::insert(
            "INSERT INTO medical_records (patient_id, professional_id, appointment_id, clinical_notes, prescription, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $patientId,
                $professionalId,
                $validated['appointment_id'] ?? null,
                $validated['clinical_notes'] ?? null,
                $validated['prescription'] ?? null,
            ]
        );

        $record = Database::fetchOne(
            "SELECT mr.*, a.scheduled_at, a.status as appointment_status 
             FROM medical_records mr 
             LEFT JOIN appointments a ON mr.appointment_id = a.id 
             WHERE mr.id = ?",
            [$id]
        );

        Response::json(['data' => $this->formatRecord($record)], 201);
    }

    public function patientRecords(Request $request): void
    {
        $patientId = $request->user['patient']['id'] ?? null;

        if (!$patientId) {
            Response::json(['data' => []]);
        }

        $sql = "SELECT mr.*, a.scheduled_at, a.status as appointment_status
                FROM medical_records mr
                LEFT JOIN appointments a ON mr.appointment_id = a.id
                WHERE mr.patient_id = ?
                ORDER BY mr.created_at DESC";
        $records = Database::fetchAll($sql, [$patientId]);

        $formatted = array_map([$this, 'formatRecord'], $records);
        Response::json(['data' => $formatted]);
    }

    private function formatRecord(array $r): array
    {
        return [
            'id'              => (int) $r['id'],
            'patient_id'      => (int) $r['patient_id'],
            'professional_id' => (int) $r['professional_id'],
            'clinical_notes'  => $r['clinical_notes'] ?? null,
            'prescription'    => $r['prescription'] ?? null,
            'appointment'     => $r['appointment_id'] ? [
                'id'           => (int) $r['appointment_id'],
                'scheduled_at' => $r['scheduled_at'] ?? null,
                'status'       => $r['appointment_status'] ?? null,
            ] : null,
            'created_at'      => $r['created_at'],
        ];
    }

    private function professionalCanAccessPatient(int $professionalId, int $patientId): bool
    {
        $patient = Database::fetchOne(
            "SELECT p.id
             FROM patients p
             WHERE p.id = ?
               AND (
                   p.professional_id = ?
                   OR EXISTS (
                       SELECT 1
                       FROM appointments a
                       WHERE a.patient_id = p.id AND a.professional_id = ?
                   )
               )",
            [$patientId, $professionalId, $professionalId]
        );

        return $patient !== null;
    }
}
