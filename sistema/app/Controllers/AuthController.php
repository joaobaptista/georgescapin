<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

class AuthController
{
    public function register(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'sometimes|in:patient,professional',
            'cpf'      => 'nullable|string|max:20',
            'phone'    => 'nullable|string|max:20',
        ]);

        $validated = $validator->validated();

        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$validated['email']]);
        if ($existing) {
            Response::error('Este e-mail já está em uso.', 422, ['email' => ['Este e-mail já está em uso.']]);
        }

        $role = $validated['role'] ?? 'patient';
        $hashedPassword = Auth::hashPassword($validated['password']);

        $userId = Database::insert(
            "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))",
            [$validated['name'], $validated['email'], $hashedPassword, $role]
        );

        if ($role === 'patient') {
            Database::insert(
                "INSERT INTO patients (user_id, cpf, phone, created_at, updated_at) VALUES (?, ?, ?, datetime('now'), datetime('now'))",
                [$userId, $validated['cpf'] ?? null, $validated['phone'] ?? null]
            );
        } elseif ($role === 'professional') {
            Database::insert(
                "INSERT INTO professionals (user_id, specialty, created_at, updated_at) VALUES (?, 'Clínico Geral', datetime('now'), datetime('now'))",
                [$userId]
            );
        }

        $user = Database::fetchOne("SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ?", [$userId]);
        $user['patient'] = Database::fetchOne("SELECT * FROM patients WHERE user_id = ?", [$userId]);
        $user['professional'] = Database::fetchOne("SELECT * FROM professionals WHERE user_id = ?", [$userId]);

        $token = Auth::createToken($userId);

        Response::json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $validated = $validator->validated();

        $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$validated['email']]);

        if (!$user || !Auth::verifyPassword($validated['password'], $user['password'])) {
            Response::json(['message' => 'Credenciais inválidas.'], 401);
        }

        unset($user['password']);
        $user['patient'] = Database::fetchOne("SELECT * FROM patients WHERE user_id = ?", [$user['id']]);
        $user['professional'] = Database::fetchOne("SELECT * FROM professionals WHERE user_id = ?", [$user['id']]);

        $token = Auth::createToken($user['id']);

        Response::json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): void
    {
        $token = $request->getBearerToken();
        if ($token) {
            Auth::revokeToken($token);
        }

        Response::json(['message' => 'Logout realizado com sucesso.']);
    }

    public function user(Request $request): void
    {
        Response::json($request->user);
    }

    public function activateAccount(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $validated = $validator->validated();

        $isValid = Auth::validatePasswordResetToken($validated['email'], $validated['token']);

        if (!$isValid) {
            Response::json(['error' => 'Token inválido ou expirado.'], 400);
        }

        $user = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$validated['email']]);
        if (!$user) {
            Response::json(['error' => 'Usuário não encontrado.'], 404);
        }

        $hashedPassword = Auth::hashPassword($validated['password']);
        Database::execute(
            "UPDATE users SET password = ?, updated_at = datetime('now') WHERE id = ?",
            [$hashedPassword, $user['id']]
        );

        Auth::deletePasswordResetToken($validated['email']);

        Response::json(['success' => true, 'message' => 'Conta ativada com sucesso!']);
    }

    public function updateProfile(Request $request): void
    {
        $user = $request->user;

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:20',
            'cpf'        => 'nullable|string|max:20',
            'birth_date' => 'nullable|string',
        ]);

        $validated = $validator->validated();

        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$validated['email'], $user['id']]);
        if ($existing) {
            Response::error('Este e-mail já está em uso.', 422, ['email' => ['Este e-mail já está em uso.']]);
        }

        Database::execute(
            "UPDATE users SET name = ?, email = ?, updated_at = datetime('now') WHERE id = ?",
            [$validated['name'], $validated['email'], $user['id']]
        );

        if ($user['role'] === 'patient' && !empty($user['patient']['id'])) {
            Database::execute(
                "UPDATE patients SET phone = ?, cpf = ?, birth_date = ?, updated_at = datetime('now') WHERE id = ?",
                [
                    $validated['phone'] ?? $user['patient']['phone'],
                    $validated['cpf'] ?? $user['patient']['cpf'],
                    $validated['birth_date'] ?? $user['patient']['birth_date'],
                    $user['patient']['id'],
                ]
            );
        }

        Response::json(['message' => 'Profile updated successfully']);
    }

    public function updatePassword(Request $request): void
    {
        $user = $request->user;

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $validated = $validator->validated();

        $dbUser = Database::fetchOne("SELECT password FROM users WHERE id = ?", [$user['id']]);

        if (!Auth::verifyPassword($validated['current_password'], $dbUser['password'])) {
            Response::json(['message' => 'A senha atual está incorreta.'], 400);
        }

        $newHashed = Auth::hashPassword($validated['password']);
        Database::execute(
            "UPDATE users SET password = ?, updated_at = datetime('now') WHERE id = ?",
            [$newHashed, $user['id']]
        );

        Response::json(['message' => 'Password updated successfully']);
    }
}
