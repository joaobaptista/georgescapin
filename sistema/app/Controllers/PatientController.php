<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ClinicalAccess;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class PatientController
{
    /** Domínio reservado (.invalid, RFC 2606) dos pacientes cadastrados sem e-mail. */
    private const PLACEHOLDER_EMAIL_DOMAIN = '@sem-email.invalid';

    public function index(Request $request): void
    {
        $professionalId = $request->user['professional']['id'] ?? null;

        if (!$professionalId) {
            Response::json(['message' => 'Apenas profissionais de saúde têm acesso à listagem de pacientes.'], 403);
        }

        $sql = "SELECT p.*, u.name, u.email, u.activated_at
                FROM patients p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE " . ClinicalAccess::PATIENT_SCOPE_SQL . "
                ORDER BY p.id DESC";
        $patients = Database::fetchAll($sql, ClinicalAccess::patientScopeParams((int) $professionalId));

        Response::json(['data' => array_map([self::class, 'formatPatient'], $patients)]);
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

        $patient = $this->findPatient($id);

        if (!$patient) {
            Response::json(['message' => 'Paciente não encontrado.'], 404);
        }

        $professionalId = (int) ($request->user['professional']['id'] ?? 0);
        if ($userRole === 'professional' && (!$professionalId || !ClinicalAccess::professionalCanAccessPatient($professionalId, (int) $patient['id']))) {
            Response::json(['message' => 'Acesso não autorizado aos dados deste paciente.'], 403);
        }

        $data = self::formatPatient($patient);
        // Só quem cadastrou o paciente gera o link de ativação da conta dele.
        $data['can_generate_activation_link'] = $data['portal_status'] === 'pending'
            && (int) ($patient['professional_id'] ?? 0) === $professionalId
            && $professionalId > 0;

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
        if ($email) {
            $this->assertEmailAvailable($email);
        } else {
            $email = 'paciente_' . bin2hex(random_bytes(6)) . self::PLACEHOLDER_EMAIL_DOMAIN;
        }

        // A conta nasce pendente (activated_at nulo) e com senha aleatória que
        // ninguém conhece; o paciente cria a própria senha pelo link de ativação.
        $tempPassword = Auth::hashPassword(bin2hex(random_bytes(16)));
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

        Response::json(['data' => self::formatPatient($this->findPatient($patientId))], 201);
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

        $sql = "SELECT p.*, u.name, u.email, u.activated_at
                FROM patients p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE (u.name LIKE ? OR p.cpf LIKE ? OR p.phone LIKE ?)
                  AND " . ClinicalAccess::PATIENT_SCOPE_SQL . "
                LIMIT 15";
        $params = array_merge(
            ["%{$query}%", "%{$query}%", "%{$query}%"],
            ClinicalAccess::patientScopeParams((int) $professionalId)
        );

        $patients = Database::fetchAll($sql, $params);

        Response::json(['data' => array_map([self::class, 'formatPatient'], $patients)]);
    }

    /**
     * Gera o link para o paciente criar a própria senha. Não há envio
     * automático: a clínica entrega o link ao paciente (WhatsApp, presencial).
     *
     * Só vale para contas que nunca foram ativadas e apenas para o profissional
     * que cadastrou o paciente. Uma conta já ativa não pode ter a senha trocada
     * por aqui: isso daria ao profissional a conta do paciente, com prontuários
     * de outros profissionais. Para contas ativas, a administração usa
     * scripts/password-reset-link.php.
     */
    public function activationLink(Request $request): void
    {
        $id = (int) $request->param('id');
        $professionalId = (int) ($request->user['professional']['id'] ?? 0);

        $patient = $this->findPatient($id);
        if (!$patient || !$professionalId || !ClinicalAccess::professionalCanAccessPatient($professionalId, $id)) {
            Response::json(['message' => 'Paciente não encontrado entre os seus pacientes.'], 404);
        }

        if ((int) ($patient['professional_id'] ?? 0) !== $professionalId) {
            Response::json(['message' => 'Somente o profissional que cadastrou o paciente pode gerar o link de ativação.'], 403);
        }

        if (!empty($patient['activated_at'])) {
            Response::json([
                'message' => 'Esta conta já está ativa. Se o paciente esqueceu a senha, a administração da clínica gera um link de redefinição.',
            ], 409);
        }

        $validated = Validator::make($request->all(), ['email' => 'nullable|email|max:255'])->validated();
        $email = $patient['email'];
        $newEmail = $validated['email'] ?? null;

        if ($newEmail !== null && strcasecmp($newEmail, (string) $email) !== 0) {
            $this->assertEmailAvailable($newEmail);
            Database::execute(
                "UPDATE users SET email = ?, updated_at = datetime('now') WHERE id = ?",
                [$newEmail, $patient['user_id']]
            );
            Database::execute("DELETE FROM password_reset_tokens WHERE email = ?", [$email]);
            $email = $newEmail;
        }

        if (self::isPlaceholderEmail($email)) {
            Response::error('Informe o e-mail do paciente: ele será o login de acesso ao portal.', 422, [
                'email' => ['Informe o e-mail do paciente para gerar o link de ativação.'],
            ]);
        }

        $token = Auth::createPasswordResetToken($email);

        Response::json(['data' => [
            'email'           => $email,
            'activation_path' => '/ativar-conta?' . http_build_query(['email' => $email, 'token' => $token]),
            'expires_in_hours' => Auth::PASSWORD_RESET_TTL_HOURS,
        ]]);
    }

    private function findPatient(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT p.*, u.name, u.email, u.activated_at
             FROM patients p
             LEFT JOIN users u ON p.user_id = u.id
             WHERE p.id = ?",
            [$id]
        );
    }

    private function assertEmailAvailable(string $email): void
    {
        if (Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email])) {
            Response::error('Este e-mail já está em uso.', 422, [
                'email' => ['Este e-mail já pertence a outra conta. Se o paciente já tem cadastro, peça que ele agende pelo portal.'],
            ]);
        }
    }

    /** Endereços internos de pacientes sem e-mail (atual e o formato antigo). */
    private static function isPlaceholderEmail(?string $email): bool
    {
        return $email === null
            || str_ends_with($email, self::PLACEHOLDER_EMAIL_DOMAIN)
            || preg_match('/^paciente_[0-9a-f]+@nuva\.com\.br$/', $email) === 1;
    }

    private static function formatPatient(array $p): array
    {
        $email = $p['email'] ?? null;

        return [
            'id'            => (int) $p['id'],
            'name'          => $p['name'] ?? null,
            'email'         => self::isPlaceholderEmail($email) ? null : $email,
            'cpf'           => $p['cpf'] ?? null,
            'phone'         => $p['phone'] ?? null,
            'birth_date'    => $p['birth_date'] ?? null,
            'portal_status' => empty($p['activated_at']) ? 'pending' : 'active',
            'created_at'    => $p['created_at'] ?? null,
        ];
    }
}
