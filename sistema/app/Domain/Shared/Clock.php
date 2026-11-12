<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Current time in the clinic's timezone. Injected so rules are testable. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
