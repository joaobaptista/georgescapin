<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Scheduling\ScheduleAppointment;
use App\Application\Shared\Actor;
use App\Application\Shared\LocalDateTime;
use App\Domain\Clinical\ClinicalAccess;
use App\Domain\Identity\Role;
use App\Domain\Scheduling\Agenda;
use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\Conflict;
use App\Domain\Shared\RuleViolation;
use Tests\Support\FixedClock;
use Tests\Support\MemoryAppointments;
use Tests\Support\MemoryPatients;
use Tests\Support\MemoryProcedures;
use Tests\Support\MemoryProfessionals;
use Tests\Support\TestCase;

final class SchedulingTest extends TestCase
{
    private MemoryPatients $patients;
    private MemoryAppointments $appointments;
    private ScheduleAppointment $schedule;

    public function __construct()
    {
        $procedures = new MemoryProcedures();
        $clock = new FixedClock();
        $this->patients = new MemoryPatients();
        $this->appointments = new MemoryAppointments($procedures);
        $this->schedule = new ScheduleAppointment(
            $this->appointments,
            $procedures,
            new MemoryProfessionals(),
            new ClinicalAccess($this->patients),
            new Agenda($this->appointments, $procedures),
            new LocalDateTime($clock),
            $clock,
        );
    }

    private static function professional(): Actor
    {
        return new Actor(5, Role::Professional, null, 1, 'h', null);
    }

    public function testProfessionalCannotBookAnUnlinkedPatient(): void
    {
        // Booking would create the link to the patient's records (the original BOLA).
        self::assertThrows(AccessDenied::class, fn () => ($this->schedule)(self::professional(), ['patient_id' => 7, 'scheduled_at' => '2030-02-01 10:00']));
        self::assertSame([], $this->appointments->appointments);
    }

    public function testPatientBookingCreatesTheLinkAndTheSlotIsThenTaken(): void
    {
        $patient = new Actor(9, Role::Patient, 7, null, 'h', null);
        ($this->schedule)($patient, ['professional_id' => 1, 'scheduled_at' => '2030-02-01T10:00']);
        $this->patients->links[] = [7, 1];

        self::assertThrows(Conflict::class, fn () => ($this->schedule)(self::professional(), ['patient_id' => 7, 'scheduled_at' => '2030-02-01 10:15']));
        ($this->schedule)(self::professional(), ['patient_id' => 7, 'scheduled_at' => '2030-02-01 10:30']);
        self::assertSame(2, count($this->appointments->appointments));
    }

    public function testInvalidDatesAndUnknownProfessionalsAreRejected(): void
    {
        $patient = new Actor(9, Role::Patient, 7, null, 'h', null);
        self::assertThrows(RuleViolation::class, fn () => ($this->schedule)($patient, ['professional_id' => 1, 'scheduled_at' => '2030-02-30 10:00']), 'inválidos');
        self::assertThrows(RuleViolation::class, fn () => ($this->schedule)($patient, ['professional_id' => 42, 'scheduled_at' => '2030-02-01 10:00']), 'Profissional');
    }
}
