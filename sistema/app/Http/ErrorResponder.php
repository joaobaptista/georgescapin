<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Conflict;
use App\Domain\Shared\DomainError;
use App\Domain\Shared\InvalidSecret;
use App\Domain\Shared\NotFound;
use App\Domain\Shared\RuleViolation;
use App\Domain\Shared\TooManyAttempts;
use App\Domain\Shared\Unauthenticated;
use App\Http\Validation\ValidationFailed;
use App\Infrastructure\Config;

/** The single place where failures become HTTP responses. */
final class ErrorResponder
{
    private const STATUS = [
        ValidationFailed::class => 422,
        RuleViolation::class => 422,
        NotFound::class => 404,
        AccessDenied::class => 403,
        Conflict::class => 409,
        InvalidSecret::class => 400,
        Unauthenticated::class => 401,
        TooManyAttempts::class => 429,
    ];

    public function __construct(private readonly Config $config)
    {
    }

    public function render(\Throwable $error): Response
    {
        if ($error instanceof ValidationFailed) {
            return Response::error($error->getMessage(), 422, $error->errors);
        }
        if ($error instanceof DomainError) {
            $response = Response::error($error->getMessage(), self::STATUS[$error::class] ?? 422, $error->errors());

            return $error instanceof TooManyAttempts ? $response->withHeader('Retry-After', (string) $error->retryAfterSeconds) : $response;
        }

        // Unexpected failure: details only in the server log, never to the client.
        error_log(sprintf('[nuva] %s: %s em %s:%d', $error::class, $error->getMessage(), $error->getFile(), $error->getLine()));
        $payload = ['message' => 'Erro interno do servidor. Tente novamente em instantes.'];
        if ($this->config->debug()) {
            $payload['error'] = $error->getMessage();
        }

        return Response::json($payload, 500);
    }
}
