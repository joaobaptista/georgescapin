<?php

declare(strict_types=1);

namespace App\Domain\Clinical;

interface MedicalRecordRepository
{
    public function append(MedicalRecord $record): int;
}
