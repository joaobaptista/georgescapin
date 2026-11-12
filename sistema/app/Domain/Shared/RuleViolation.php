<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Dados válidos na forma, mas que violam uma regra de negócio (422). */
final class RuleViolation extends DomainError
{
}
