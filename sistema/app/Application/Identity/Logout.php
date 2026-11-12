<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Shared\Actor;
use App\Domain\Identity\SessionRepository;

final class Logout
{
    public function __construct(private readonly SessionRepository $sessions)
    {
    }

    public function __invoke(Actor $actor): void
    {
        $this->sessions->revoke($actor->sessionTokenHash);
    }
}
