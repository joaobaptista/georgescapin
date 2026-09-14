<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOCustomPageRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getAll(bool $onlyPublished = false): array
    {
        try {
            $sql = "SELECT * FROM custom_pages";
            if ($onlyPublished) {
                $sql .= " WHERE is_published = 1";
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM custom_pages WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM custom_pages WHERE slug = ? AND is_published = 1 LIMIT 1");
            $stmt->execute([$slug]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO custom_pages (title, slug, subtitle, content, banner_image, meta_description, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['subtitle'] ?? null,
            $data['content'],
            $data['banner_image'] ?? null,
            $data['meta_description'] ?? null,
            $data['is_published'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE custom_pages
            SET title = ?, slug = ?, subtitle = ?, content = ?, banner_image = ?, meta_description = ?, is_published = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['subtitle'] ?? null,
            $data['content'],
            $data['banner_image'] ?? null,
            $data['meta_description'] ?? null,
            $data['is_published'] ?? 1,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM custom_pages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
