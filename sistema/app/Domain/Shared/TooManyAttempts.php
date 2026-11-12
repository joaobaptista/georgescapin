<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Rate limit reached (429). */
final class TooManyAttempts extends DomainError
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('Muitas tentativas. Por favor, aguarde antes de tentar novamente.');
    }
}
