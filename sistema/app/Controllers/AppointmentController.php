<?php

namespace App\Controllers;

use App\Core\ClinicalAccess;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class AppointmentController
{
    public function index(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;
        $patientId = $request->user['patient']['id'] ?? null;

        $sql = "SELECT a.*, 
                       pat.phone as patient_phone, 
                       pu.name as patient_name,
                       proc.name as procedure_name,
                       pro.specialty as professional_specialty,
                       prou.name as professional_name
                FROM appointments a
                LEFT JOIN patients pat ON a.patient_id = pat.id
                LEFT JOIN users pu ON pat.user_id = pu.id
                LEFT JOIN procedures proc ON a.procedure_id = proc.id
                LEFT JOIN professionals pro ON a.professional_id = pro.id
                LEFT JOIN users prou ON pro.user_id = prou.id";
        
        $params = [];
        if ($professionalId) {
            $sql .= " WHERE a.professional_id = ?";
            $params[] = $professionalId;
        } elseif ($patientId) {
            $sql .= " WHERE a.patient_id = ?";
            $params[] = $patientId;
        } else {
            Response::json(['data' => []]);
        }

        $sql .= " ORDER BY a.scheduled_at ASC";

        $rows = Database::fetchAll($sql, $params);
        $data = array_map([$this, 'formatAppointment'], $rows);

        Response::json(['data' => $data]);
    }

    public function patientAppointments(Request $request): void
    {
        $patientId = $request->user['patient']['id'] ?? null;
        if (!$patientId) {
            Response::json(['data' => []]);
        }

        $sql = "SELECT a.*, 
                       pat.phone as patient_phone, 
                       pu.name as patient_name,
                       proc.name as procedure_name,
                       pro.specialty as professional_specialty,
                       prou.name as professional_name
                FROM appointments a
                LEFT JOIN patients pat ON a.patient_id = pat.id
                LEFT JOIN users pu ON pat.user_id = pu.id
                LEFT JOIN procedures proc ON a.procedure_id = proc.id
                LEFT JOIN professionals pro ON a.professional_id = pro.id
                LEFT JOIN users prou ON pro.user_id = prou.id
                WHERE a.patient_id = ?
                ORDER BY a.scheduled_at ASC";

        $rows = Database::fetchAll($sql, [$patientId]);
        $data = array_map([$this, 'formatAppointment'], $rows);

        Response::json(['data' => $data]);
    }

    public function store(Request $request): void
    {
        $isProfessional = ($request->user['role'] ?? '') === 'professional';
        $isPatient = ($request->user['role'] ?? '') === 'patient';

        $rules = [
            'procedure_id' => 'nullable|integer',
            'scheduled_at' => 'required|string',
            'notes'        => 'nullable|string',
        ];

        if ($isProfessional) {
            $rules['patient_id'] = 'required|integer';
        } else {
            $rules['professional_id'] = 'required|integer';
        }

        $validator = Validator::make($request->all(), $rules);
        $validated = $validator->validated();

        if ($isProfessional) {
            $professionalId = $request->user['professional']['id'] ?? null;
            $patientId = (int) $validated['patient_id'];
        } else {
            $patientId = $request->user['patient']['id'] ?? null;
            $professionalId = (int) $validated['professional_id'];
        }

        if (!$professionalId || !$patientId) {
            Response::json(['message' => 'Perfil do usuário incompleto para realizar o agendamento.'], 422);
        }

        if ($isProfessional) {
            // O agendamento cria vínculo de acesso ao prontuário. Por isso o
            // profissional só agenda pacientes que já estão vinculados a ele;
            // um vínculo novo nasce do cadastro na clínica ou de uma solicitação
            // feita pelo próprio paciente.
            if (!ClinicalAccess::professionalCanAccessPatient((int) $professionalId, $patientId)) {
                Response::json(['message' => 'Paciente não encontrado entre os seus pacientes.'], 403);
            }
        } elseif (!Database::fetchOne("SELECT id FROM professionals WHERE id = ?", [$professionalId])) {
            Response::error('Profissional não encontrado.', 422, ['professional_id' => ['Profissional não encontrado.']]);
        }

        // Pacientes só solicitam horários futuros; o profissional pode
        // registrar atendimentos retroativos.
        $scheduledAt = $this->scheduledAtOrFail($validated['scheduled_at'], !$isProfessional);
        $procedureId = $this->procedureOrFail($validated['procedure_id'] ?? null, (int) $professionalId);

        $id = Database::insert(
            "INSERT INTO appointments (patient_id, professional_id, procedure_id, scheduled_at, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, 'scheduled', ?, datetime('now'), datetime('now'))",
            [
                $patientId,
                $professionalId,
                $procedureId,
                $scheduledAt,
                $validated['notes'] ?? null,
            ]
        );

        $row = $this->getAppointmentById($id);
        Response::json(['data' => $this->formatAppointment($row)], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('appointment') ?: (int) $request->param('id');
        $appointment = $this->getAppointmentById($id);

        if (!$appointment) {
            Response::json(['message' => 'Agendamento não encontrado.'], 404);
        }

        // Validação de autorização BOLA/IDOR
        $userProId = $request->user['professional']['id'] ?? null;
        $userPatId = $request->user['patient']['id'] ?? null;

        if ($appointment['professional_id'] !== $userProId && $appointment['patient_id'] !== $userPatId) {
            Response::json(['message' => 'Acesso não autorizado a este agendamento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'procedure_id' => 'nullable|integer',
            'scheduled_at' => 'required|string',
            'notes'        => 'nullable|string',
        ]);

        $validated = $validator->validated();

        // Quem edita como paciente só pode mover o horário para o futuro.
        $editsAsPatient = $appointment['professional_id'] !== $userProId;
        $scheduledAt = $this->scheduledAtOrFail($validated['scheduled_at'], false);
        if ($editsAsPatient && $scheduledAt !== $appointment['scheduled_at']) {
            $scheduledAt = $this->scheduledAtOrFail($validated['scheduled_at'], true);
        }

        $procedureId = array_key_exists('procedure_id', $validated) && $validated['procedure_id'] !== null
            ? $this->procedureOrFail($validated['procedure_id'], (int) $appointment['professional_id'])
            : $appointment['procedure_id'];

        Database::execute(
            "UPDATE appointments SET procedure_id = ?, scheduled_at = ?, notes = ?, updated_at = datetime('now') WHERE id = ?",
            [
                $procedureId,
                $scheduledAt,
                $validated['notes'] ?? $appointment['notes'],
                $id,
            ]
        );

        $row = $this->getAppointmentById($id);
        Response::json(['data' => $this->formatAppointment($row)]);
    }

    public function updateStatus(Request $request): void
    {
        $id = (int) $request->param('appointment') ?: (int) $request->param('id');
        $appointment = $this->getAppointmentById($id);

        if (!$appointment) {
            Response::json(['message' => 'Agendamento não encontrado.'], 404);
        }

        // Validação de autorização BOLA/IDOR
        $userProId = $request->user['professional']['id'] ?? null;
        $userPatId = $request->user['patient']['id'] ?? null;

        if ($appointment['professional_id'] !== $userProId && $appointment['patient_id'] !== $userPatId) {
            Response::json(['message' => 'Acesso não autorizado a este agendamento.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,confirmed,canceled,completed',
        ]);

        $validated = $validator->validated();

        // Pacientes só podem cancelar agendamentos
        if ($userPatId && $appointment['patient_id'] === $userPatId && $appointment['professional_id'] !== $userProId) {
            if ($validated['status'] !== 'canceled') {
                Response::json(['message' => 'Pacientes só possuem permissão para cancelar agendamentos.'], 403);
            }
        }

        Database::execute(
            "UPDATE appointments SET status = ?, updated_at = datetime('now') WHERE id = ?",
            [$validated['status'], $id]
        );

        $row = $this->getAppointmentById($id);
        Response::json(['data' => $this->formatAppointment($row)]);
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('appointment') ?: (int) $request->param('id');
        $appointment = $this->getAppointmentById($id);

        if (!$appointment) {
            Response::noContent();
        }

        // Validação de autorização BOLA/IDOR
        $userProId = $request->user['professional']['id'] ?? null;
        $userPatId = $request->user['patient']['id'] ?? null;

        if ($appointment['professional_id'] !== $userProId && $appointment['patient_id'] !== $userPatId) {
            Response::json(['message' => 'Acesso não autorizado a este agendamento.'], 403);
        }

        Database::execute("DELETE FROM appointments WHERE id = ?", [$id]);

        Response::noContent();
    }

    /**
     * Aceita "AAAA-MM-DD HH:MM[:SS]" (com espaço ou "T") e devolve sempre
     * "AAAA-MM-DD HH:MM:SS". Datas inexistentes, como 2031-02-30, são recusadas.
     */
    private function scheduledAtOrFail(mixed $value, bool $mustBeFuture): string
    {
        $value = trim((string) $value);
        // Horários são digitados no fuso da clínica; a comparação com "agora"
        // usa esse mesmo fuso, independentemente do fuso do servidor.
        $clinicTimezone = new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');
        $date = null;
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
            $candidate = \DateTimeImmutable::createFromFormat('!' . $format, $value, $clinicTimezone);
            if ($candidate !== false && $candidate->format($format) === $value) {
                $date = $candidate;
                break;
            }
        }

        if ($date === null) {
            Response::error('Data e horário inválidos.', 422, [
                'scheduled_at' => ['Informe data e horário no formato AAAA-MM-DD HH:MM.'],
            ]);
        }

        if ($mustBeFuture && $date <= new \DateTimeImmutable('now', $clinicTimezone)) {
            Response::error('Escolha um horário futuro.', 422, [
                'scheduled_at' => ['Não é possível agendar em uma data ou horário que já passou.'],
            ]);
        }

        return $date->format('Y-m-d H:i:s');
    }

    /** O procedimento, quando informado, precisa pertencer ao profissional do agendamento. */
    private function procedureOrFail(mixed $procedureId, int $professionalId): ?int
    {
        if ($procedureId === null || $procedureId === '') {
            return null;
        }

        $procedure = Database::fetchOne(
            "SELECT id FROM procedures WHERE id = ? AND professional_id = ?",
            [(int) $procedureId, $professionalId]
        );
        if (!$procedure) {
            Response::error('Procedimento não encontrado para este profissional.', 422, [
                'procedure_id' => ['Procedimento não encontrado para este profissional.'],
            ]);
        }

        return (int) $procedureId;
    }

    private function getAppointmentById(int $id): ?array
    {
        $sql = "SELECT a.*, 
                       pat.phone as patient_phone, 
                       pu.name as patient_name,
                       proc.name as procedure_name,
                       pro.specialty as professional_specialty,
                       prou.name as professional_name
                FROM appointments a
                LEFT JOIN patients pat ON a.patient_id = pat.id
                LEFT JOIN users pu ON pat.user_id = pu.id
                LEFT JOIN procedures proc ON a.procedure_id = proc.id
                LEFT JOIN professionals pro ON a.professional_id = pro.id
                LEFT JOIN users prou ON pro.user_id = prou.id
                WHERE a.id = ?";
        return Database::fetchOne($sql, [$id]);
    }

    private function formatAppointment(array $a): array
    {
        return [
            'id'              => (int) $a['id'],
            'scheduled_at'    => $a['scheduled_at'],
            'status'          => $a['status'],
            'notes'           => $a['notes'],
            'patient_id'      => (int) $a['patient_id'],
            'patient'         => [
                'id'    => (int) $a['patient_id'],
                'name'  => $a['patient_name'] ?? null,
                'phone' => $a['patient_phone'] ?? null,
            ],
            'procedure_id'    => $a['procedure_id'] ? (int) $a['procedure_id'] : null,
            'procedure'       => $a['procedure_name'] ? [
                'id'   => (int) $a['procedure_id'],
                'name' => $a['procedure_name'],
            ] : null,
            'professional_id' => (int) $a['professional_id'],
            'professional'    => [
                'id'   => (int) $a['professional_id'],
                'name' => $a['professional_name'] ?? null,
            ],
            'created_at'      => $a['created_at'],
        ];
    }
}
