<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Catalog\Procedure;
use App\Domain\Scheduling\Agenda;
use App\Domain\Scheduling\Appointment;
use App\Domain\Scheduling\AppointmentStatus;
use App\Domain\Scheduling\TimeSlot;
use App\Domain\Shared\Conflict;
use Tests\Support\MemoryAppointments;
use Tests\Support\MemoryProcedures;
use Tests\Support\TestCase;

final class AgendaTest extends TestCase
{
    public function testBackToBackSlotsDoNotOverlap(): void
    {
        $first = TimeSlot::starting(new \DateTimeImmutable('2030-01-10 10:00'), 45);
        self::assertTrue(!$first->overlaps(TimeSlot::starting(new \DateTimeImmutable('2030-01-10 10:45'), 30)));
        self::assertTrue($first->overlaps(TimeSlot::starting(new \DateTimeImmutable('2030-01-10 10:44'), 30)));
        self::assertSame(30, TimeSlot::starting(new \DateTimeImmutable('2030-01-10 10:00'), null)->minutes);
    }

    public function testBusySlotUsesTheProcedureDurationAndIgnoresCanceled(): void
    {
        $procedures = new MemoryProcedures();
        $procedures->save(new Procedure(null, 20, 'Fios', null, null, 0, 90), new \DateTimeImmutable());
        $appointments = new MemoryAppointments($procedures);
        $appointments->save(Appointment::restore(1, 10, 20, 1, new \DateTimeImmutable('2030-01-10 09:00'), AppointmentStatus::Scheduled, null), new \DateTimeImmutable());
        $appointments->save(Appointment::restore(2, 11, 20, null, new \DateTimeImmutable('2030-01-10 14:00'), AppointmentStatus::Canceled, null), new \DateTimeImmutable());
        $agenda = new Agenda($appointments, $procedures);

        self::assertThrows(Conflict::class, fn () => $agenda->assertFree(20, $agenda->slotFor(new \DateTimeImmutable('2030-01-10 10:00'), null)), '09:00 às 10:30');
        $agenda->assertFree(20, $agenda->slotFor(new \DateTimeImmutable('2030-01-10 10:30'), null));
        $agenda->assertFree(20, $agenda->slotFor(new \DateTimeImmutable('2030-01-10 14:00'), null));
        $agenda->assertFree(21, $agenda->slotFor(new \DateTimeImmutable('2030-01-10 09:30'), null));
        // An appointment does not conflict with itself when rescheduled.
        $agenda->assertFree(20, $agenda->slotFor(new \DateTimeImmutable('2030-01-10 09:15'), 1), 1);
        self::assertTrue(true);
    }
}
