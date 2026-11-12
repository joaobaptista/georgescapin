<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Senha atual ou token de uso único incorretos (400). */
final class InvalidSecret extends DomainError
{
}
