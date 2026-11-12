<?php

declare(strict_types=1);

namespace App\Application\Identity;

interface AccountQueries
{
    /** User with its patient and professional profiles, as returned by /api/user. */
    public function currentUser(int $userId): ?array;
}
