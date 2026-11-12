<?php

declare(strict_types=1);

namespace App\Application\Identity;

/** Health data: sessions end after a working day, or sooner when idle. */
final class SessionPolicy
{
    public function __construct(
        public readonly int $lifetimeHours = 12,
        public readonly int $idleMinutes = 120,
    ) {
    }
}
