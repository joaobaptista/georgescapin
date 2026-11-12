<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Queries;

use App\Application\Clinical\ProfessionalQueries;
use App\Infrastructure\Persistence\Db;

final class PdoProfessionalQueries implements ProfessionalQueries
{
    public function __construct(private readonly Db $db)
    {
    }

    public function dashboard(int $professionalId): ?array
    {
        $p = $this->db->one('SELECT p.*, u.name, u.email FROM professionals p JOIN users u ON p.user_id = u.id WHERE p.id = ?', [$professionalId]);
        if ($p === null) {
            return null;
        }

        return [
            'id' => (int) $p['id'],
            'user_id' => (int) $p['user_id'],
            'specialty' => $p['specialty'],
            'crm' => $p['crm'],
            'address_city' => $p['address_city'],
            'address_state' => $p['address_state'],
            'bio' => $p['bio'],
            'rating' => (float) ($p['rating'] ?? 5.0),
            'user' => ['id' => (int) $p['user_id'], 'name' => $p['name'], 'email' => $p['email']],
        ];
    }

    public function search(string $term): array
    {
        $like = "%{$term}%";
        $rows = $this->db->all(
            'SELECT p.*, u.name FROM professionals p JOIN users u ON p.user_id = u.id
             WHERE u.name LIKE ? OR p.specialty LIKE ? OR p.crm LIKE ? OR p.address_city LIKE ? LIMIT 15',
            [$like, $like, $like, $like],
        );

        return array_map(static fn (array $p): array => [
            'id' => (int) $p['id'],
            'name' => $p['name'],
            'specialty' => $p['specialty'],
            'city' => $p['address_city'],
            'state' => $p['address_state'],
            'rating' => (float) ($p['rating'] ?? 5.0),
        ], $rows);
    }
}
