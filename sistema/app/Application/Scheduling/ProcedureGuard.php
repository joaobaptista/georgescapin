<?php

declare(strict_types=1);

namespace App\Application\Scheduling;

use App\Domain\Catalog\ProcedureRepository;
use App\Domain\Shared\RuleViolation;

/** An appointment's procedure must belong to its professional. */
final class ProcedureGuard
{
    public static function ownedBy(ProcedureRepository $procedures, mixed $procedureId, int $professionalId): ?int
    {
        if ($procedureId === null || $procedureId === '') {
            return null;
        }
        if (!$procedures->find((int) $procedureId)?->belongsTo($professionalId)) {
            throw new RuleViolation('Procedimento não encontrado para este profissional.', [
                'procedure_id' => ['Procedimento não encontrado para este profissional.'],
            ]);
        }

        return (int) $procedureId;
    }
}
