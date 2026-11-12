<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Identity\Session;
use App\Domain\Identity\SessionRepository;

final class PdoSessionRepository implements SessionRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function create(int $userId, string $tokenHash, \DateTimeImmutable $createdAt, \DateTimeImmutable $expiresAt): void
    {
        $this->db->insert(
            "INSERT INTO personal_access_tokens (user_id, token, name, created_at, expires_at, last_used_at) VALUES (?, ?, 'auth_token', ?, ?, ?)",
            [$userId, $tokenHash, Db::ts($createdAt), Db::ts($expiresAt), Db::ts($createdAt)],
        );
    }

    public function findByTokenHash(string $tokenHash): ?Session
    {
        $row = $this->db->one('SELECT * FROM personal_access_tokens WHERE token = ?', [$tokenHash]);
        if ($row === null) {
            return null;
        }
        $createdAt = new \DateTimeImmutable((string) $row['created_at']);

        return new Session(
            (int) $row['id'],
            (int) $row['user_id'],
            $createdAt,
            // Tokens issued before expiry tracking existed are treated as expired.
            !empty($row['expires_at']) ? new \DateTimeImmutable((string) $row['expires_at']) : $createdAt,
            !empty($row['last_used_at']) ? new \DateTimeImmutable((string) $row['last_used_at']) : null,
        );
    }

    public function touch(int $sessionId, \DateTimeImmutable $at): void
    {
        $this->db->execute('UPDATE personal_access_tokens SET last_used_at = ? WHERE id = ?', [Db::ts($at), $sessionId]);
    }

    public function revoke(string $tokenHash): void
    {
        $this->db->execute('DELETE FROM personal_access_tokens WHERE token = ?', [$tokenHash]);
    }

    public function revokeAllForUser(int $userId, ?string $exceptTokenHash = null): void
    {
        $this->db->execute('DELETE FROM personal_access_tokens WHERE user_id = ? AND token <> ?', [$userId, $exceptTokenHash ?? '']);
    }
}
