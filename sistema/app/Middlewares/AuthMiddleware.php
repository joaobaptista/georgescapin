<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->getBearerToken();

        if (!$token) {
            Response::json(['message' => 'Não autenticado.'], 401);
        }

        $user = Auth::validateToken($token);

        if (!$user) {
            Response::json(['message' => 'Token inválido ou expirado.'], 401);
        }

        $request->user = $user;
    }
}
