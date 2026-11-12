<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Identity\Role;
use App\Domain\Shared\AccessDenied;

/** The authenticated user performing a use case. */
final class Actor
{
    public function __construct(
        public readonly int $userId,
        public readonly Role $role,
        public readonly ?int $patientId,
        public readonly ?int $professionalId,
        public readonly string $sessionTokenHash,
        public readonly ?string $ip,
    ) {
    }

    public function professionalIdOrFail(string $message = 'Apenas profissionais de saúde podem realizar esta ação.'): int
    {
        return $this->professionalId ?? throw new AccessDenied($message);
    }
}
