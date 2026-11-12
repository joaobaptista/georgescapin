<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Audit\AuditTrail;
use App\Domain\Shared\Clock;

final class PdoAuditTrail implements AuditTrail
{
    public function __construct(private readonly Db $db, private readonly Clock $clock)
    {
    }

    public function record(string $action, ?int $actorUserId, ?int $patientId = null, ?string $subjectType = null, ?int $subjectId = null, ?string $ip = null): void
    {
        $this->db->insert(
            'INSERT INTO audit_logs (action, actor_user_id, patient_id, subject_type, subject_id, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$action, $actorUserId, $patientId, $subjectType, $subjectId, $ip, Db::ts($this->clock->now())],
        );
    }
}
