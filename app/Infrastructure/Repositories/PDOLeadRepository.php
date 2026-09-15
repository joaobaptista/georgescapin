<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOLeadRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function createContact(string $nome, string $telefone, string $mensagem): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO contact_messages (nome, telefone, mensagem) VALUES (?, ?, ?)");
        return $stmt->execute([$nome, $telefone, $mensagem]);
    }

    public function getAllContacts(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getContactsPaginated(int $page = 1, int $perPage = 10, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $params = [];
        $whereSql = '';
        if (!empty($search)) {
            $whereSql = "WHERE nome LIKE :q OR telefone LIKE :q OR mensagem LIKE :q";
            $params[':q'] = "%{$search}%";
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM contact_messages {$whereSql}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);
        $lastPage = (int)ceil($total / $perPage);

        $sql = "SELECT * FROM contact_messages {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
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
    }

    public function updateContactStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function addNewsletterSubscriber(string $email): bool
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
        return $stmt->execute([$email]);
    }

    public function getAllNewsletterSubscribers(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getNewsletterPaginated(int $page = 1, int $perPage = 10, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $params = [];
        $whereSql = '';
        if (!empty($search)) {
            $whereSql = "WHERE email LIKE :q";
            $params[':q'] = "%{$search}%";
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM newsletter_subscribers {$whereSql}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch()['total'] ?? 0);
        $lastPage = (int)ceil($total / $perPage);

        $sql = "SELECT * FROM newsletter_subscribers {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
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
    }

    public function countNewLeads(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'novo'");
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function countSubscribers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function deleteContact(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteNewsletterSubscriber(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

