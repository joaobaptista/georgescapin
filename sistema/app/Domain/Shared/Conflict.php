<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** A ação conflita com o estado atual, como um horário ocupado (409). */
final class Conflict extends DomainError
{
}
