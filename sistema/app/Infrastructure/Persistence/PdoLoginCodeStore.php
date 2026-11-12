<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Identity\LoginCodeStore;

final class PdoLoginCodeStore implements LoginCodeStore
{
    public function __construct(private readonly Db $db)
    {
    }

    public function put(string $codeHash, int $userId, \DateTimeImmutable $createdAt): void
    {
        $this->db->execute('DELETE FROM login_codes WHERE created_at < ?', [Db::ts($createdAt->modify('-1 hour'))]);
        $this->db->insert('INSERT INTO login_codes (code_hash, user_id, created_at) VALUES (?, ?, ?)', [$codeHash, $userId, Db::ts($createdAt)]);
    }

    public function take(string $codeHash): ?array
    {
        $row = $this->db->one('SELECT user_id, created_at FROM login_codes WHERE code_hash = ?', [$codeHash]);
        if ($row === null || $this->db->execute('DELETE FROM login_codes WHERE code_hash = ?', [$codeHash]) === 0) {
            return null;
        }

        return ['userId' => (int) $row['user_id'], 'createdAt' => new \DateTimeImmutable((string) $row['created_at'])];
    }
}
