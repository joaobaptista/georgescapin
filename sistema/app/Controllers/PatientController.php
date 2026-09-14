<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class PatientController
{
    public function index(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;

        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde têm acesso à listagem de pacientes.'], 403);
        }

        $sql = "SELECT p.*, u.name, u.email 
                FROM patients p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.professional_id = ? OR p.professional_id IS NULL
                ORDER BY p.id DESC";
        $patients = Database::fetchAll($sql, [$professionalId]);

        $formatted = array_map(function ($p) {
            return [
                'id'         => (int) $p['id'],
                'name'       => $p['name'] ?? null,
                'email'      => $p['email'] ?? null,
                'cpf'        => $p['cpf'] ?? null,
                'phone'      => $p['phone'] ?? null,
                'birth_date' => $p['birth_date'] ?? null,
                'created_at' => $p['created_at'] ?? null,
            ];
        }, $patients);

        Response::json(['data' => $formatted]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('patient') ?: (int) $request->param('id');
        $userRole = $request->user['role'] ?? '';
        $userPatientId = $request->user['patient']['id'] ?? null;

        // Se for paciente, só pode ver a si próprio
        if ($userRole === 'patient' && $userPatientId !== $id) {
            Response::json(['message' => 'Acesso não autorizado aos dados deste paciente.'], 403);
        }

        $sql = "SELECT p.*, u.name, u.email 
                FROM patients p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.id = ?";
        $patient = Database::fetchOne($sql, [$id]);

        if (!$patient) {
            Response::json(['message' => 'Paciente não encontrado.'], 404);
        }

        $professionalId = (int) ($request->user['professional']['id'] ?? 0);
        if ($userRole === 'professional' && (!$professionalId || !$this->professionalCanAccessPatient($professionalId, (int) $patient['id']))) {
            Response::json(['message' => 'Acesso não autorizado aos dados deste paciente.'], 403);
        }

        $data = [
            'id'         => (int) $patient['id'],
            'name'       => $patient['name'] ?? null,
            'email'      => $patient['email'] ?? null,
            'cpf'        => $patient['cpf'] ?? null,
            'phone'      => $patient['phone'] ?? null,
            'birth_date' => $patient['birth_date'] ?? null,
            'created_at' => $patient['created_at'] ?? null,
        ];

        Response::json(['data' => $data]);
    }

    public function store(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;
        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde podem cadastrar novos pacientes.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'cpf'        => 'required|string|max:20',
            'birth_date' => 'nullable|string',
            'phone'      => 'required|string|max:20',
            'email'      => 'nullable|email|max:255',
            'allergies'  => 'nullable|string',
            'notes'      => 'nullable|string',
        ]);

        $validated = $validator->validated();

        $email = $validated['email'] ?? null;
        if (!$email || Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email])) {
            $email = 'paciente_' . uniqid() . '@nuva.com.br';
        }

        $tempPassword = Auth::hashPassword('placeholder_' . uniqid());
        $userId = Database::insert(
            "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'patient', datetime('now'), datetime('now'))",
            [$validated['name'], $email, $tempPassword]
        );

        $patientId = Database::insert(
            "INSERT INTO patients (user_id, professional_id, cpf, phone, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $userId,
                $professionalId,
                $validated['cpf'],
                $validated['phone'],
                $validated['birth_date'] ?? null,
            ]
        );

        // Medical record if notes/allergies provided
        if (!empty($validated['allergies']) || !empty($validated['notes'])) {
            $notes = "Cadastro Inicial.\n";
            if (!empty($validated['allergies'])) {
                $notes .= "Alergias/Restrições: {$validated['allergies']}\n";
            }
            if (!empty($validated['notes'])) {
                $notes .= "Informações: {$validated['notes']}\n";
            }

            Database::insert(
                "INSERT INTO medical_records (patient_id, professional_id, clinical_notes, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))",
                [$patientId, $professionalId, $notes]
            );
        }

        $data = [
            'id'         => $patientId,
            'name'       => $validated['name'],
            'email'      => $email,
            'cpf'        => $validated['cpf'],
            'phone'      => $validated['phone'],
            'birth_date' => $validated['birth_date'] ?? null,
            'created_at' => date('c'),
        ];

        Response::json(['data' => $data], 201);
    }

    public function search(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;
        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde podem buscar pacientes.'], 403);
        }

        $query = trim($request->get('q', ''));
        if (mb_strlen($query) < 1) {
            Response::json(['data' => []]);
        }
        
        $sql = "SELECT p.*, u.name, u.email 
                FROM patients p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE (u.name LIKE ? OR p.cpf LIKE ? OR p.phone LIKE ?)
                  AND (p.professional_id = ? OR p.professional_id IS NULL)
                LIMIT 15";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%", $professionalId];

        $patients = Database::fetchAll($sql, $params);

        $formatted = array_map(function ($p) {
            return [
                'id'         => (int) $p['id'],
                'name'       => $p['name'] ?? null,
                'email'      => $p['email'] ?? null,
                'cpf'        => $p['cpf'] ?? null,
                'phone'      => $p['phone'] ?? null,
                'birth_date' => $p['birth_date'] ?? null,
            ];
        }, $patients);

        Response::json(['data' => $formatted]);
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
