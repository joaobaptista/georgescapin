<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Identity\AccountQueries;
use App\Infrastructure\Persistence\Db;

final class PdoAccountQueries implements AccountQueries
{
    public function __construct(private readonly Db $db)
    {
    }

    public function currentUser(int $userId): ?array
    {
        $user = $this->db->one('SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            return null;
        }
        $user['id'] = (int) $user['id'];
        $user['patient'] = $this->db->one('SELECT * FROM patients WHERE user_id = ?', [$userId]);
        $user['professional'] = $this->db->one('SELECT * FROM professionals WHERE user_id = ?', [$userId]);

        return $user;
    }
}
