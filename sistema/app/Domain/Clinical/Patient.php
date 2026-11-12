<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

/** Clinical profile of a patient account. */
final class Patient
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $userId,
        /** Professional who registered the patient at the clinic, if any. */
        public readonly ?int $registeredBy,
        public ?string $cpf,
        public ?string $phone,
        public ?string $birthDate,
    ) {
    }

    public function wasRegisteredBy(int $professionalId): bool
    {
        return $this->registeredBy === $professionalId;
    }
}
