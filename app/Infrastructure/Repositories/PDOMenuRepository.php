<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOMenuRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getAll(bool $onlyActive = false): array
    {
        try {
            $sql = "SELECT * FROM menu_items";
            if ($onlyActive) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY sort_order ASC, id ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll() : [];
        } catch (\Throwable $e) {
            return [
                ['id' => 1, 'label' => 'Início', 'url' => '/', 'target' => '_self', 'sort_order' => 1, 'is_active' => 1, 'is_button' => 0],
                ['id' => 2, 'label' => 'A Clínica', 'url' => '/clinica', 'target' => '_self', 'sort_order' => 2, 'is_active' => 1, 'is_button' => 0],
                ['id' => 3, 'label' => 'Procedimentos', 'url' => '/procedimentos', 'target' => '_self', 'sort_order' => 3, 'is_active' => 1, 'is_button' => 0],
                ['id' => 4, 'label' => 'Full Face', 'url' => '/harmonizacao-facial', 'target' => '_self', 'sort_order' => 4, 'is_active' => 1, 'is_button' => 0],
                ['id' => 5, 'label' => 'Blog', 'url' => '/blog', 'target' => '_self', 'sort_order' => 5, 'is_active' => 1, 'is_button' => 0],
                ['id' => 6, 'label' => 'Contato', 'url' => '/contato', 'target' => '_self', 'sort_order' => 6, 'is_active' => 1, 'is_button' => 0]
            ];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO menu_items (label, url, target, sort_order, is_active, is_button)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['label'],
            $data['url'],
            $data['target'] ?? '_self',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['is_button'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE menu_items
            SET label = ?, url = ?, target = ?, sort_order = ?, is_active = ?, is_button = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['label'],
            $data['url'],
            $data['target'] ?? '_self',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['is_button'] ?? 0,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
