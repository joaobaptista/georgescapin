<?php

namespace App\Presentation\Controllers;

use App\Infrastructure\Security\Auth;

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('admin');
        }

        require admin_view_path('login');
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Sessão de segurança expirada. Por favor, tente fazer login novamente.');
            redirect('admin/login');
            return;
        }

        // Proteção contra ataques de força bruta (Rate Limiter)
        $lockoutTime = 120; // 2 minutos
        $maxAttempts = 5;

        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;

        if ($attempts >= $maxAttempts && ($now - $lastAttempt) < $lockoutTime) {
            $remaining = $lockoutTime - ($now - $lastAttempt);
            flash('error', "Muitas tentativas incorretas. Por segurança, aguarde {$remaining} segundos antes de tentar novamente.");
            redirect('admin/login');
            return;
        }

        // Se o tempo de lockout passou, reseta as tentativas
        if (($now - $lastAttempt) >= $lockoutTime) {
            $_SESSION['login_attempts'] = 0;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            flash('error', 'Preencha o e-mail e a senha.');
            redirect('admin/login');
            return;
        }

        if (Auth::attempt($email, $password)) {
            // Sucesso: limpa o contador de tentativas
            unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
            flash('success', 'Bem-vindo ao Painel Administrativo!');
            redirect('admin');
        } else {
            // Falha: incrementa o contador de tentativas
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_login_attempt'] = time();

            $remainingAttempts = max(0, $maxAttempts - $_SESSION['login_attempts']);
            if ($remainingAttempts > 0) {
                flash('error', "Credenciais inválidas. ({$remainingAttempts} tentativas restantes).");
            } else {
                flash('error', "Limite de tentativas excedido. Acesso bloqueado temporariamente por 2 minutos.");
            }
            redirect('admin/login');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('admin/login');
    }
}
