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

    public function getContactsPaginated(int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM contact_messages");
        $total = (int)($countStmt->fetch()['total'] ?? 0);
        $lastPage = (int)ceil($total / $perPage);

        $stmt = $this->pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
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

    public function getNewsletterPaginated(int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
        $total = (int)($countStmt->fetch()['total'] ?? 0);
        $lastPage = (int)ceil($total / $perPage);

        $stmt = $this->pdo->prepare("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
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
}
