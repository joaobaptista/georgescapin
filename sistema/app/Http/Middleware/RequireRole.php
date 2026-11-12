<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\AccessDenied;
use App\Http\Request;
use App\Http\Response;

final class RequireRole implements Middleware
{
    public function process(Request $request, callable $next, ?string $parameter = null): Response
    {
        if ($request->actor()->role->value !== $parameter) {
            throw new AccessDenied('Acesso não autorizado para o seu perfil.');
        }

        return $next($request);
    }
}
