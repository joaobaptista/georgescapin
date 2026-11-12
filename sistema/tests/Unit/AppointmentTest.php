<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Scheduling\Appointment;
use App\Domain\Scheduling\AppointmentStatus as S;
use App\Domain\Scheduling\Party;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\RuleViolation;
use Tests\Support\TestCase;

final class AppointmentTest extends TestCase
{
    private function appointment(S $status = S::Scheduled): Appointment
    {
        return Appointment::restore(1, 10, 20, null, new \DateTimeImmutable('2030-02-01 10:00'), $status, null);
    }

    public function testStatusTransitionsFollowTheLifecycle(): void
    {
        $allowed = [
            'scheduled' => ['confirmed', 'completed', 'canceled'],
            'confirmed' => ['completed', 'canceled'],
            'canceled' => ['scheduled'],
            'completed' => [],
        ];
        foreach (S::cases() as $from) {
            foreach (S::cases() as $to) {
                self::assertSame(in_array($to->value, $allowed[$from->value], true), $from->canTransitionTo($to), "{$from->value} → {$to->value}:");
            }
        }
    }

    public function testPatientCannotBookInThePastButProfessionalCan(): void
    {
        $now = new \DateTimeImmutable('2030-01-10 09:00');
        $past = new \DateTimeImmutable('2030-01-09 09:00');
        self::assertThrows(RuleViolation::class, fn () => Appointment::book(10, 20, null, $past, null, Party::Patient, $now), 'futuro');
        self::assertSame(S::Scheduled, Appointment::book(10, 20, null, $past, null, Party::Professional, $now)->status());
    }

    public function testStrangersAreDenied(): void
    {
        self::assertThrows(AccessDenied::class, fn () => $this->appointment()->partyOf(99, 98));
        self::assertSame(Party::Patient, $this->appointment()->partyOf(10, null));
        self::assertSame(Party::Professional, $this->appointment()->partyOf(null, 20));
    }

    public function testPatientCanOnlyCancel(): void
    {
        self::assertThrows(AccessDenied::class, fn () => $this->appointment()->changeStatus(S::Confirmed, Party::Patient));
        $appointment = $this->appointment(S::Confirmed);
        $appointment->changeStatus(S::Canceled, Party::Patient);
        self::assertSame(S::Canceled, $appointment->status());
    }

    public function testCompletedAppointmentIsImmutableHistory(): void
    {
        $completed = $this->appointment(S::Completed);
        self::assertThrows(RuleViolation::class, fn () => $completed->changeStatus(S::Canceled, Party::Patient));
        self::assertThrows(RuleViolation::class, fn () => $completed->changeStatus(S::Canceled, Party::Professional));
        self::assertThrows(RuleViolation::class, fn () => $completed->reschedule(new \DateTimeImmutable('2030-03-01 10:00'), null, null, Party::Professional, new \DateTimeImmutable('2030-01-10')));
        self::assertThrows(RuleViolation::class, fn () => $completed->assertRemovableBy(Party::Professional), 'histórico clínico');
    }

    public function testPatientsNeverDelete(): void
    {
        self::assertThrows(AccessDenied::class, fn () => $this->appointment()->assertRemovableBy(Party::Patient));
        $this->appointment(S::Canceled)->assertRemovableBy(Party::Professional);
        self::assertTrue(true);
    }

    public function testPatientReschedulesOnlyToTheFuture(): void
    {
        $now = new \DateTimeImmutable('2030-01-10 09:00');
        $appointment = $this->appointment();
        self::assertThrows(RuleViolation::class, fn () => $appointment->reschedule(new \DateTimeImmutable('2030-01-09 10:00'), null, null, Party::Patient, $now));
        // Keeping the same time (only notes changed) is allowed.
        $appointment->reschedule(new \DateTimeImmutable('2030-02-01 10:00'), null, 'nota', Party::Patient, $now);
        self::assertSame('nota', $appointment->notes());
    }
}
