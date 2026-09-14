<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOPostRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getAll(bool $onlyPublished = true): array
    {
        try {
            $sql = "SELECT * FROM posts";
            if ($onlyPublished) {
                $sql .= " WHERE is_published = 1";
            }
            $sql .= " ORDER BY created_at DESC, id DESC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getPaginated(int $page = 1, int $perPage = 3, bool $onlyPublished = true): array
    {
        try {
            $page = max(1, $page);
            $offset = ($page - 1) * $perPage;

            $countSql = "SELECT COUNT(*) as total FROM posts";
            if ($onlyPublished) {
                $countSql .= " WHERE is_published = 1";
            }
            $countStmt = $this->pdo->query($countSql);
            $total = (int)($countStmt ? ($countStmt->fetch()['total'] ?? 0) : 0);
            $lastPage = (int)ceil($total / $perPage);

            $sql = "SELECT * FROM posts";
            if ($onlyPublished) {
                $sql .= " WHERE is_published = 1";
            }
            $sql .= " ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();

            return [
                'data' => $data,
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, $lastPage)
            ];
        } catch (\Throwable $e) {
            return ['data' => [], 'total' => 0, 'current_page' => 1, 'per_page' => $perPage, 'last_page' => 1];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = ?");
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
            $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE slug = ? AND is_published = 1");
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
            INSERT INTO posts (title, slug, author, summary, content, image_url, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['author'] ?? 'Dr. George',
            $data['summary'],
            $data['content'],
            $data['image_url'] ?? '',
            $data['is_published'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE posts 
            SET title = ?, slug = ?, author = ?, summary = ?, content = ?, image_url = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['author'] ?? 'Dr. George',
            $data['summary'],
            $data['content'],
            $data['image_url'] ?? '',
            $data['is_published'] ?? 1,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function countPublished(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM posts WHERE is_published = 1");
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
}
