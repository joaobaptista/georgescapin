<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Identity\Role;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $id): ?User
    {
        return $this->map($this->db->one('SELECT * FROM users WHERE id = ?', [$id]));
    }

    public function findByEmail(string $email): ?User
    {
        return $this->map($this->db->one('SELECT * FROM users WHERE email = ?', [$email]));
    }

    public function emailInUse(string $email, ?int $exceptUserId = null): bool
    {
        return $this->db->one('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $exceptUserId ?? 0]) !== null;
    }

    public function save(User $user, \DateTimeImmutable $now): int
    {
        if ($user->id === null) {
            return $this->db->insert(
                'INSERT INTO users (name, email, password, role, activated_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$user->name, $user->email, $user->passwordHash, $user->role->value, Db::ts($user->activatedAt), Db::ts($now), Db::ts($now)],
            );
        }

        $this->db->execute(
            'UPDATE users SET name = ?, email = ?, password = ?, activated_at = ?, updated_at = ? WHERE id = ?',
            [$user->name, $user->email, $user->passwordHash, Db::ts($user->activatedAt), Db::ts($now), $user->id],
        );

        return $user->id;
    }

    private function map(?array $row): ?User
    {
        if ($row === null) {
            return null;
        }

        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password'],
            Role::from((string) $row['role']),
            !empty($row['activated_at']) ? new \DateTimeImmutable((string) $row['activated_at']) : null,
        );
    }
}
