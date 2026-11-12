<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Clinical\PatientQueries;
use App\Application\Clinical\RegisterPatient;
use App\Infrastructure\Persistence\Db;
use App\Infrastructure\Persistence\PdoPatientRepository;

final class PdoPatientQueries implements PatientQueries
{
    private const SELECT = 'SELECT p.*, u.name, u.email, u.activated_at FROM patients p LEFT JOIN users u ON p.user_id = u.id';

    public function __construct(private readonly Db $db)
    {
    }

    public function find(int $patientId): ?array
    {
        $row = $this->db->one(self::SELECT . ' WHERE p.id = ?', [$patientId]);

        return $row !== null ? self::view($row) : null;
    }

    public function linkedTo(int $professionalId): array
    {
        $rows = $this->db->all(
            self::SELECT . ' WHERE ' . PdoPatientRepository::LINKED_TO_PROFESSIONAL . ' ORDER BY p.id DESC',
            [$professionalId, $professionalId],
        );

        return array_map([self::class, 'view'], $rows);
    }

    public function searchLinkedTo(int $professionalId, string $term): array
    {
        $like = "%{$term}%";
        $rows = $this->db->all(
            self::SELECT . ' WHERE (u.name LIKE ? OR p.cpf LIKE ? OR p.phone LIKE ?) AND ' . PdoPatientRepository::LINKED_TO_PROFESSIONAL . ' LIMIT 15',
            [$like, $like, $like, $professionalId, $professionalId],
        );

        return array_map([self::class, 'view'], $rows);
    }

    private static function view(array $p): array
    {
        $email = $p['email'] ?? null;

        return [
            'id' => (int) $p['id'],
            'name' => $p['name'] ?? null,
            // Internal placeholder addresses are never shown.
            'email' => RegisterPatient::isPlaceholderEmail($email) ? null : $email,
            'cpf' => $p['cpf'] ?? null,
            'phone' => $p['phone'] ?? null,
            'birth_date' => $p['birth_date'] ?? null,
            'portal_status' => empty($p['activated_at']) ? 'pending' : 'active',
            'created_at' => $p['created_at'] ?? null,
        ];
    }
}
