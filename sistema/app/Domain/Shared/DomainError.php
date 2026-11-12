<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Base for business-rule failures. Carries a user-facing message (pt-BR) and
 * optional field errors; the HTTP layer maps each subclass to a status code.
 */
abstract class DomainError extends \DomainException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(string $message, private readonly array $errors = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
