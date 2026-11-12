<?php

declare(strict_types=1);

namespace App\Http\Validation;

final class ValidationFailed extends \RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Erro de validação.');
    }
}
