<?php

declare(strict_types=1);

namespace App\Domain\Identity;

enum Role: string
{
    case Patient = 'patient';
    case Professional = 'professional';
}
