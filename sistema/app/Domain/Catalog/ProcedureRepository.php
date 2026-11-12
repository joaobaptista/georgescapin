<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

interface ProcedureRepository
{
    public function find(int $id): ?Procedure;

    public function save(Procedure $procedure, \DateTimeImmutable $now): int;

    public function remove(int $id): void;
}
