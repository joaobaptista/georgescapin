<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Shared\RateLimiter;
use App\Domain\Shared\TooManyAttempts;
use App\Http\Request;
use App\Http\Response;

/** Per-IP limit for a route. Parameter "max,minutes", e.g. "10,1". */
final class Throttle implements Middleware
{
    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function process(Request $request, callable $next, ?string $parameter = null): Response
    {
        [$max, $minutes] = array_map('intval', explode(',', $parameter ?? '10,1') + [1 => 1]);
        $retryAfter = $this->limiter->hit("ip:{$request->clientIp}:{$request->path()}", $max, $minutes * 60);
        if ($retryAfter !== null) {
            throw new TooManyAttempts($retryAfter);
        }

        return $next($request);
    }
}
