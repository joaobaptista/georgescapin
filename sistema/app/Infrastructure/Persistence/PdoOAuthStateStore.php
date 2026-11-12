<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Identity\OAuthStateStore;

final class PdoOAuthStateStore implements OAuthStateStore
{
    public function __construct(private readonly Db $db)
    {
    }

    public function put(string $stateHash, string $codeVerifier, string $nonce, \DateTimeImmutable $createdAt): void
    {
        // Housekeeping: abandoned sign-ins older than a day.
        $this->db->execute('DELETE FROM oauth_states WHERE created_at < ?', [Db::ts($createdAt->modify('-1 day'))]);
        $this->db->insert('INSERT INTO oauth_states (state_hash, code_verifier, nonce, created_at) VALUES (?, ?, ?, ?)', [$stateHash, $codeVerifier, $nonce, Db::ts($createdAt)]);
    }

    public function take(string $stateHash): ?array
    {
        $row = $this->db->one('SELECT code_verifier, nonce, created_at FROM oauth_states WHERE state_hash = ?', [$stateHash]);
        if ($row === null || $this->db->execute('DELETE FROM oauth_states WHERE state_hash = ?', [$stateHash]) === 0) {
            return null; // already used by a concurrent request
        }

        return ['codeVerifier' => (string) $row['code_verifier'], 'nonce' => (string) $row['nonce'], 'createdAt' => new \DateTimeImmutable((string) $row['created_at'])];
    }
}
