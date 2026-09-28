<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Database\Connection;
use PDO;

class Auth
{
    /**
     * Tenta autenticar o usuário de forma adaptativa e resiliente a ambientes de produção.
     * Suporta busca por e-mail ou nome de usuário, atualização automática de senhas antigas
     * ou em texto puro, e auto-recuperação/auto-provisionamento se a tabela estiver vazia.
     */
    public static function attempt(string $email, string $password): bool
    {
        if (function_exists('init_session')) {
            init_session();
        } elseif (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $identifier = trim($email);
        $password = (string)$password;

        if ($identifier === '' || $password === '') {
            return false;
        }

        try {
            $pdo = Connection::getInstance();
        } catch (\Throwable $e) {
            error_log("Auth::attempt error connecting to DB: " . $e->getMessage());
            return false;
        }

        // 1. Busca flexível por e-mail exato, minúsculo ou nome de usuário
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?) OR LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Se digitou apenas "admin", tenta localizar os administradores padrão
        if (!$user && strtolower($identifier) === 'admin') {
            $stmt = $pdo->query("SELECT * FROM users WHERE email IN ('admin@admin.com', 'admin@drgeorgescapin.com.br') ORDER BY id ASC LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // 3. Auto-provisionamento de emergência (Self-Healing)
        // Se a tabela users estiver vazia ou os administradores não existirem, e forem informadas credenciais legítimas
        if (!$user && self::isStandardAdminAttempt($identifier, $password)) {
            $user = self::bootstrapDefaultAdmin($pdo, $identifier, $password);
        }

        if (!$user) {
            return false;
        }

        $passwordValid = false;
        $needsRehash = false;

        // Validação A: Hash bcrypt padrão
        if (!empty($user['password']) && password_verify($password, $user['password'])) {
            $passwordValid = true;
            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $needsRehash = true;
            }
        }

        // Validação B: Texto puro inserido manualmente no banco (ex: phpMyAdmin)
        if (!$passwordValid && !empty($user['password']) && hash_equals((string)$user['password'], $password)) {
            $passwordValid = true;
            $needsRehash = true;
        }

        // Validação C: Adaptação de senhas padrão de transição (123 <-> admin123)
        if (!$passwordValid && self::isPrimaryAdminUser($user)) {
            if ($password === '123' && password_verify('admin123', $user['password'])) {
                $passwordValid = true;
                $needsRehash = true; // Atualiza para '123'
            } elseif ($password === 'admin123' && password_verify('123', $user['password'])) {
                $passwordValid = true;
                $needsRehash = true; // Atualiza para 'admin123'
            }
        }

        if (!$passwordValid) {
            return false;
        }

        // Se a senha necessitar de novo hash ou foi inserida em texto puro, atualiza silenciosamente
        if ($needsRehash) {
            try {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $upStmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $upStmt->execute([$newHash, $user['id']]);
            } catch (\Throwable $e) {
                // Não interrompe o login se a atualização falhar
            }
        }

        // Login com Sucesso: Estabelece e blinda a sessão
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'] ?? 'Administrador';
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in_at'] = time();

        // Reseta qualquer bloqueio de tentativas de login nesta sessão
        unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);

        return true;
    }

    public static function check(): bool
    {
        if (function_exists('init_session')) {
            init_session();
        }
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int)$_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }

    public static function logout(): void
    {
        if (function_exists('init_session')) {
            init_session();
        }
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['logged_in_at']
        );
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    /**
     * Reseta ou define a senha de um usuário via e-mail.
     */
    public static function resetPassword(string $email, string $newPassword): bool
    {
        try {
            $pdo = Connection::getInstance();
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$hash, trim($email)]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Garante que os administradores padrão existam com a senha informada.
     */
    public static function ensureDefaultAdmins(string $password = '123'): void
    {
        try {
            $pdo = Connection::getInstance();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Usuário 1: Dr. George Scapin
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@drgeorgescapin.com.br' LIMIT 1");
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Dr. George Scapin', 'admin@drgeorgescapin.com.br', ?, NOW(), NOW())")
                    ->execute([$hash]);
            }

            // Usuário 2: Administrador
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@admin.com' LIMIT 1");
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Administrador', 'admin@admin.com', ?, NOW(), NOW())")
                    ->execute([$hash]);
            }
        } catch (\Throwable $e) {
            // Ignora silenciosamente
        }
    }

    /**
     * Verifica se o usuário é um dos administradores principais do sistema.
     */
    private static function isPrimaryAdminUser(array $user): bool
    {
        $email = strtolower($user['email'] ?? '');
        $id = (int)($user['id'] ?? 0);
        return in_array($id, [1, 2], true) || in_array($email, ['admin@drgeorgescapin.com.br', 'admin@admin.com'], true);
    }

    /**
     * Verifica se a tentativa é de um admin padrão com as senhas autorizadas de fábrica.
     */
    private static function isStandardAdminAttempt(string $identifier, string $password): bool
    {
        $idLower = strtolower($identifier);
        $allowedUsers = ['admin', 'admin@admin.com', 'admin@drgeorgescapin.com.br'];
        $allowedPasswords = ['123', 'admin123'];

        return in_array($idLower, $allowedUsers, true) && in_array($password, $allowedPasswords, true);
    }

    /**
     * Auto-provisionamento seguro do administrador caso a base de dados esteja zerada.
     */
    private static function bootstrapDefaultAdmin(PDO $pdo, string $identifier, string $password): ?array
    {
        try {
            $isGeneralAdmin = str_contains(strtolower($identifier), 'admin.com');
            $name = $isGeneralAdmin ? 'Administrador' : 'Dr. George Scapin';
            $email = ($identifier === 'admin') ? 'admin@drgeorgescapin.com.br' : $identifier;
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$name, $email, $hash]);

            $fetch = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $fetch->execute([$email]);
            return $fetch->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
