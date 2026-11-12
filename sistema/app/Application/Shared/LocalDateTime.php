<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Clock;
use App\Domain\Shared\RuleViolation;

/** Parses "AAAA-MM-DD HH:MM[:SS]" (space or "T") typed in the clinic's timezone. */
final class LocalDateTime
{
    private const FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'];

    public function __construct(private readonly Clock $clock)
    {
    }

    public function parse(mixed $value, string $field = 'scheduled_at'): \DateTimeImmutable
    {
        $value = trim((string) $value);
        $timezone = $this->clock->now()->getTimezone();

        foreach (self::FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value, $timezone);
            // Round-trip check rejects impossible dates such as 2031-02-30.
            if ($date !== false && $date->format($format) === $value) {
                return $date;
            }
        }

        throw new RuleViolation('Data e horário inválidos.', [
            $field => ['Informe data e horário no formato AAAA-MM-DD HH:MM.'],
        ]);
    }
}
