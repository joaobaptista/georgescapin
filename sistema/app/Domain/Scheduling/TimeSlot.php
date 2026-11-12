<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** The time an appointment occupies in the professional's agenda. */
final class TimeSlot
{
    public const DEFAULT_MINUTES = 30;

    public readonly \DateTimeImmutable $end;

    private function __construct(public readonly \DateTimeImmutable $start, public readonly int $minutes)
    {
        $this->end = $start->modify("+{$minutes} minutes");
    }

    /** Procedures without a valid duration take the default 30 minutes. */
    public static function starting(\DateTimeImmutable $start, ?int $minutes): self
    {
        return new self($start, $minutes !== null && $minutes > 0 ? $minutes : self::DEFAULT_MINUTES);
    }

    /** Back-to-back slots (one ends when the other starts) do not overlap. */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $other->start < $this->end;
    }

    public function label(): string
    {
        return $this->start->format('d/m H:i') . ' às ' . $this->end->format('H:i');
    }
}
