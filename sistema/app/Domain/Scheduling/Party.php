<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Which side of an appointment is acting. */
enum Party
{
    case Patient;
    case Professional;
}
