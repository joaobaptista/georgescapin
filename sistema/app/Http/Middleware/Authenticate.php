<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Identity\AuthenticateSession;
use App\Domain\Shared\Unauthenticated;
use App\Http\Request;
use App\Http\Response;

final class Authenticate implements Middleware
{
    public function __construct(private readonly AuthenticateSession $authenticate)
    {
    }

    public function process(Request $request, callable $next, ?string $parameter = null): Response
    {
        $token = $request->bearerToken() ?? throw new Unauthenticated('Não autenticado.');
        $request->actor = ($this->authenticate)($token, $request->clientIp);

        return $next($request);
    }
}
