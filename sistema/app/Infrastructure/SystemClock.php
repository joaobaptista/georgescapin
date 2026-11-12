<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Shared\Clock;

final class SystemClock implements Clock
{
    private readonly \DateTimeZone $timezone;

    public function __construct(Config $config)
    {
        $this->timezone = new \DateTimeZone($config->timezone());
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }
}
