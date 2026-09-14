<?php

namespace App\Presentation\Middlewares;

use App\Infrastructure\Security\Auth;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            flash('error', 'Por favor, faça login para acessar o painel administrativo.');
            redirect('admin/login');
        }
    }
}
