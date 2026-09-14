<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOProcedureRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getAll(bool $onlyActive = true): array
    {
        try {
            $sql = "SELECT * FROM procedures";
            if ($onlyActive) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY sort_order ASC, id ASC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getPaginated(int $page = 1, int $perPage = 5, bool $onlyActive = false): array
    {
        try {
            $page = max(1, $page);
            $offset = ($page - 1) * $perPage;

            $countSql = "SELECT COUNT(*) as total FROM procedures";
            if ($onlyActive) {
                $countSql .= " WHERE is_active = 1";
            }
            $countStmt = $this->pdo->query($countSql);
            $total = (int)($countStmt ? ($countStmt->fetch()['total'] ?? 0) : 0);
            $lastPage = (int)ceil($total / $perPage);

            $sql = "SELECT * FROM procedures";
            if ($onlyActive) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY sort_order ASC, id ASC LIMIT :limit OFFSET :offset";

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
            $stmt = $this->pdo->prepare("SELECT * FROM procedures WHERE id = ?");
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
            $stmt = $this->pdo->prepare("SELECT * FROM procedures WHERE slug = ? AND is_active = 1");
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
            INSERT INTO procedures (title, slug, short_description, full_description, image_url, icon_name, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['short_description'],
            $data['full_description'] ?? '',
            $data['image_url'] ?? '',
            $data['icon_name'] ?? 'sparkles',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE procedures 
            SET title = ?, slug = ?, short_description = ?, full_description = ?, image_url = ?, icon_name = ?, sort_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['short_description'],
            $data['full_description'] ?? '',
            $data['image_url'] ?? '',
            $data['icon_name'] ?? 'sparkles',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM procedures WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
