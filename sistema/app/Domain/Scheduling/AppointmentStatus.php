<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/**
 * Appointment lifecycle:
 *
 *   scheduled ──► confirmed ──► completed (final: part of the clinical history)
 *       │             │
 *       └──────► canceled ◄────┘
 *                   │
 *                   └──► scheduled (reactivation by the professional)
 */
enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function isOpen(): bool
    {
        return $this === self::Scheduled || $this === self::Confirmed;
    }

    /** Canceled appointments free the slot; every other status keeps it busy. */
    public function occupiesSlot(): bool
    {
        return $this !== self::Canceled;
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Scheduled => in_array($next, [self::Confirmed, self::Completed, self::Canceled], true),
            self::Confirmed => in_array($next, [self::Completed, self::Canceled], true),
            self::Canceled => $next === self::Scheduled,
            self::Completed => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'agendado',
            self::Confirmed => 'confirmado',
            self::Completed => 'concluído',
            self::Canceled => 'cancelado',
        };
    }
}
