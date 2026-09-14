<?php

namespace App\Core;

class Auth
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function createToken(int $userId): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        Database::insert(
            "INSERT INTO personal_access_tokens (user_id, token, name, created_at) VALUES (?, ?, 'auth_token', datetime('now'))",
            [$userId, $hashedToken]
        );

        return $plainToken;
    }

    public static function validateToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);

        $tokenRecord = Database::fetchOne(
            "SELECT * FROM personal_access_tokens WHERE token = ?",
            [$hashedToken]
        );

        if (!$tokenRecord) {
            return null;
        }

        // Expiração de 30 dias para tokens de acesso
        if (!empty($tokenRecord['created_at'])) {
            $createdAt = strtotime($tokenRecord['created_at']);
            if ($createdAt && (time() - $createdAt) > (30 * 86400)) {
                Database::execute("DELETE FROM personal_access_tokens WHERE id = ?", [$tokenRecord['id']]);
                return null;
            }
        }

        $user = Database::fetchOne(
            "SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ?",
            [$tokenRecord['user_id']]
        );

        if (!$user) {
            return null;
        }

        // Attach patient and professional profiles
        $user['patient'] = Database::fetchOne("SELECT * FROM patients WHERE user_id = ?", [$user['id']]);
        $user['professional'] = Database::fetchOne("SELECT * FROM professionals WHERE user_id = ?", [$user['id']]);

        return $user;
    }

    public static function revokeToken(string $plainToken): void
    {
        $hashedToken = hash('sha256', $plainToken);
        Database::execute("DELETE FROM personal_access_tokens WHERE token = ?", [$hashedToken]);
    }

    public static function createPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(24));
        Database::execute("DELETE FROM password_reset_tokens WHERE email = ?", [$email]);
        Database::insert(
            "INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, datetime('now'))",
            [$email, $token]
        );
        return $token;
    }

    public static function validatePasswordResetToken(string $email, string $token): bool
    {
        $record = Database::fetchOne(
            "SELECT * FROM password_reset_tokens WHERE email = ? AND token = ?",
            [$email, $token]
        );
        if (!$record) {
            return false;
        }

        // Expiração de 1 hora para token de redefinição
        if (!empty($record['created_at'])) {
            $createdAt = strtotime($record['created_at']);
            if ($createdAt && (time() - $createdAt) > 3600) {
                Database::execute("DELETE FROM password_reset_tokens WHERE email = ?", [$email]);
                return false;
            }
        }

        return true;
    }

    public static function deletePasswordResetToken(string $email): void
    {
        Database::execute("DELETE FROM password_reset_tokens WHERE email = ?", [$email]);
    }
}
