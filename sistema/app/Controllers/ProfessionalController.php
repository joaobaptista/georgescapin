<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class ProfessionalController
{
    public function dashboard(Request $request): void
    {
        $user = $request->user;
        $professionalId = $user['professional']['id'] ?? null;

        if ($professionalId) {
            $sql = "SELECT p.*, u.name, u.email FROM professionals p JOIN users u ON p.user_id = u.id WHERE p.id = ?";
            $professional = Database::fetchOne($sql, [$professionalId]);
        } else {
            $sql = "SELECT p.*, u.name, u.email FROM professionals p JOIN users u ON p.user_id = u.id LIMIT 1";
            $professional = Database::fetchOne($sql);
        }

        if (!$professional) {
            Response::json(['error' => 'Profissional não encontrado.'], 404);
        }

        $formatted = [
            'id'            => (int) $professional['id'],
            'user_id'       => (int) $professional['user_id'],
            'specialty'     => $professional['specialty'],
            'crm'           => $professional['crm'],
            'address_city'  => $professional['address_city'],
            'address_state' => $professional['address_state'],
            'bio'           => $professional['bio'],
            'rating'        => (float) ($professional['rating'] ?? 5.0),
            'user'          => [
                'id'    => (int) $professional['user_id'],
                'name'  => $professional['name'],
                'email' => $professional['email'],
            ],
        ];

        Response::json($formatted);
    }

    public function search(Request $request): void
    {
        $query = trim($request->get('q', ''));
        if (mb_strlen($query) < 2) {
            Response::json([]);
        }

        $sql = "SELECT p.*, u.name, u.email 
                FROM professionals p 
                JOIN users u ON p.user_id = u.id 
                WHERE u.name LIKE ? OR p.specialty LIKE ? OR p.crm LIKE ? OR p.address_city LIKE ?
                LIMIT 15";
        $param = "%{$query}%";
        $professionals = Database::fetchAll($sql, [$param, $param, $param, $param]);

        $formatted = array_map(function ($p) {
            return [
                'id'        => (int) $p['id'],
                'name'      => $p['name'],
                'specialty' => $p['specialty'],
                'city'      => $p['address_city'],
                'state'     => $p['address_state'],
                'rating'    => (float) ($p['rating'] ?? 5.0),
            ];
        }, $professionals);

        Response::json($formatted);
    }
}
