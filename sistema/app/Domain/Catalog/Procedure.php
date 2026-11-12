<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Shared\AccessDenied;

/** A procedure offered by one professional (price and duration). */
final class Procedure
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $professionalId,
        public string $name,
        public ?string $category,
        public ?string $description,
        public float $basePrice,
        public int $durationMinutes,
    ) {
    }

    public function belongsTo(?int $professionalId): bool
    {
        return $professionalId !== null && $professionalId === $this->professionalId;
    }

    public function assertOwnedBy(?int $professionalId, string $action): void
    {
        if (!$this->belongsTo($professionalId)) {
            throw new AccessDenied("Acesso não autorizado para {$action} este procedimento.");
        }
    }
}
