<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Domain\Shared\AccessDenied;
use App\Domain\Shared\RuleViolation;

/**
 * Aggregate root of the agenda. Every change goes through a method that
 * enforces who may do it and which status transitions are valid.
 */
final class Appointment
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $patientId,
        public readonly int $professionalId,
        private ?int $procedureId,
        private \DateTimeImmutable $scheduledAt,
        private AppointmentStatus $status,
        private ?string $notes,
    ) {
    }

    /** Patients only request future times; professionals may record past ones. */
    public static function book(
        int $patientId,
        int $professionalId,
        ?int $procedureId,
        \DateTimeImmutable $scheduledAt,
        ?string $notes,
        Party $bookedBy,
        \DateTimeImmutable $now,
    ): self {
        if ($bookedBy === Party::Patient) {
            self::assertFuture($scheduledAt, $now);
        }

        return new self(null, $patientId, $professionalId, $procedureId, $scheduledAt, AppointmentStatus::Scheduled, $notes);
    }

    public static function restore(
        int $id,
        int $patientId,
        int $professionalId,
        ?int $procedureId,
        \DateTimeImmutable $scheduledAt,
        AppointmentStatus $status,
        ?string $notes,
    ): self {
        return new self($id, $patientId, $professionalId, $procedureId, $scheduledAt, $status, $notes);
    }

    /** Resolves which side the actor is on; strangers are denied. */
    public function partyOf(?int $patientId, ?int $professionalId): Party
    {
        if ($professionalId !== null && $professionalId === $this->professionalId) {
            return Party::Professional;
        }
        if ($patientId !== null && $patientId === $this->patientId) {
            return Party::Patient;
        }

        throw new AccessDenied('Acesso não autorizado a este agendamento.');
    }

    public function reschedule(
        \DateTimeImmutable $scheduledAt,
        ?int $procedureId,
        ?string $notes,
        Party $by,
        \DateTimeImmutable $now,
    ): void {
        if (!$this->status->isOpen()) {
            throw new RuleViolation('Somente agendamentos em aberto podem ser alterados.', [
                'status' => ["Este agendamento está {$this->status->label()}."],
            ]);
        }
        if ($by === Party::Patient && $scheduledAt != $this->scheduledAt) {
            self::assertFuture($scheduledAt, $now);
        }

        $this->scheduledAt = $scheduledAt;
        $this->procedureId = $procedureId;
        $this->notes = $notes;
    }

    public function changeStatus(AppointmentStatus $next, Party $by): void
    {
        if ($by === Party::Patient && $next !== AppointmentStatus::Canceled) {
            throw new AccessDenied('Pacientes só possuem permissão para cancelar agendamentos.');
        }
        if (!$this->status->canTransitionTo($next)) {
            throw new RuleViolation("Não é possível alterar um agendamento {$this->status->label()} para {$next->label()}.", [
                'status' => ["Transição de status inválida: {$this->status->label()} → {$next->label()}."],
            ]);
        }

        $this->status = $next;
    }

    /** Completed appointments are clinical history; patients cancel instead of deleting. */
    public function assertRemovableBy(Party $by): void
    {
        if ($by === Party::Patient) {
            throw new AccessDenied('Pacientes não podem excluir agendamentos. Use o cancelamento.');
        }
        if ($this->status === AppointmentStatus::Completed) {
            throw new RuleViolation('Atendimentos concluídos fazem parte do histórico clínico e não podem ser excluídos.');
        }
    }

    public function procedureId(): ?int
    {
        return $this->procedureId;
    }

    public function scheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function status(): AppointmentStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    private static function assertFuture(\DateTimeImmutable $scheduledAt, \DateTimeImmutable $now): void
    {
        if ($scheduledAt <= $now) {
            throw new RuleViolation('Escolha um horário futuro.', [
                'scheduled_at' => ['Não é possível agendar em uma data ou horário que já passou.'],
            ]);
        }
    }
}
