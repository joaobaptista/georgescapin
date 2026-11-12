<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Identity\PasswordResetRepository;

final class PdoPasswordResetRepository implements PasswordResetRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function replace(string $email, string $tokenHash, \DateTimeImmutable $createdAt): void
    {
        $this->forget($email);
        $this->db->insert('INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, ?)', [$email, $tokenHash, Db::ts($createdAt)]);
    }

    public function issuedAt(string $email, string $tokenHash): ?\DateTimeImmutable
    {
        $row = $this->db->one('SELECT created_at FROM password_reset_tokens WHERE email = ? AND token = ?', [$email, $tokenHash]);

        return $row !== null && !empty($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : null;
    }

    public function forget(string $email): void
    {
        $this->db->execute('DELETE FROM password_reset_tokens WHERE email = ?', [$email]);
    }
}
