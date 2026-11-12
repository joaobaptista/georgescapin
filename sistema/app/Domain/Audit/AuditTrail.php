<?php

declare(strict_types=1);

namespace App\Domain\Audit;

/**
 * Who accessed or changed sensitive data, and when (LGPD / prontuário
 * eletrônico). Entries are append-only.
 */
interface AuditTrail
{
    public function record(
        string $action,
        ?int $actorUserId,
        ?int $patientId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $ip = null,
    ): void;
}
