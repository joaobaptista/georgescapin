<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Shared\RateLimiter;
use App\Domain\Shared\Clock;

/** Fixed-window counters in the database: shared by every PHP worker, no temp files. */
final class PdoRateLimiter implements RateLimiter
{
    public function __construct(private readonly Db $db, private readonly Clock $clock)
    {
    }

    public function hit(string $key, int $maxHits, int $decaySeconds): ?int
    {
        $now = $this->clock->now();
        $row = $this->current($key, $now);

        if ($row === null) {
            $resetAt = $now->modify("+{$decaySeconds} seconds");
            $this->write($key, 1, $resetAt);

            return 1 > $maxHits ? $decaySeconds : null;
        }

        $hits = $row['hits'] + 1;
        $this->write($key, $hits, $row['reset_at']);

        return $hits > $maxHits ? max(1, $row['reset_at']->getTimestamp() - $now->getTimestamp()) : null;
    }

    public function blockedFor(string $key, int $maxHits): ?int
    {
        $now = $this->clock->now();
        $row = $this->current($key, $now);

        return $row !== null && $row['hits'] >= $maxHits ? max(1, $row['reset_at']->getTimestamp() - $now->getTimestamp()) : null;
    }

    public function clear(string $key): void
    {
        $this->db->execute('DELETE FROM rate_limits WHERE rate_key = ?', [$key]);
    }

    /** @return array{hits: int, reset_at: \DateTimeImmutable}|null the live window, if any */
    private function current(string $key, \DateTimeImmutable $now): ?array
    {
        $row = $this->db->one('SELECT hits, reset_at FROM rate_limits WHERE rate_key = ?', [$key]);
        if ($row === null) {
            return null;
        }
        $resetAt = new \DateTimeImmutable((string) $row['reset_at']);

        return $resetAt > $now ? ['hits' => (int) $row['hits'], 'reset_at' => $resetAt] : null;
    }

    private function write(string $key, int $hits, \DateTimeImmutable $resetAt): void
    {
        $updated = $this->db->execute('UPDATE rate_limits SET hits = ?, reset_at = ? WHERE rate_key = ?', [$hits, Db::ts($resetAt), $key]);
        if ($updated === 0 && $this->db->one('SELECT rate_key FROM rate_limits WHERE rate_key = ?', [$key]) === null) {
            try {
                $this->db->insert('INSERT INTO rate_limits (rate_key, hits, reset_at) VALUES (?, ?, ?)', [$key, $hits, Db::ts($resetAt)]);
            } catch (\PDOException) {
                // A concurrent request created the row first; its count stands.
            }
        }
    }
}
