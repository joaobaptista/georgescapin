<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** O ator não tem permissão para a ação (403). */
final class AccessDenied extends DomainError
{
}
