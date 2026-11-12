<?php

declare(strict_types=1);

namespace App\Application\Shared;

interface RateLimiter
{
    /** Counts one hit. Returns the seconds until reset when over the limit, null otherwise. */
    public function hit(string $key, int $maxHits, int $decaySeconds): ?int;

    /** Seconds until reset if the key is already over the limit, without counting a hit. */
    public function blockedFor(string $key, int $maxHits): ?int;

    public function clear(string $key): void;
}
