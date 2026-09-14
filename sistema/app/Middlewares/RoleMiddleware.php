<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

class RoleMiddleware
{
    public function handle(Request $request, string $requiredRole): void
    {
        if (!$request->user) {
            Response::json(['message' => 'Não autenticado.'], 401);
        }

        if (($request->user['role'] ?? '') !== $requiredRole && ($request->user['role'] ?? '') !== 'admin') {
            Response::json(['message' => 'Acesso não autorizado para o seu perfil.'], 403);
        }
    }
}
