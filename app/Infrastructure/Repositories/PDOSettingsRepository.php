<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database\Connection;
use PDO;

class PDOSettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM site_settings");
            $results = $stmt ? $stmt->fetchAll() : [];
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (\Throwable $e) {
            return [
                'site_title' => 'Dr. George Scapin | Harmonização e Estética Facial Avançada em RS',
                'meta_description' => 'Clínica Dr. George Scapin em Porto Alegre. Especialista em Estética Facial, Toxina Botulínica, Preenchimento e Harmonização Full Face.',
                'meta_keywords' => 'Dr. George Scapin, estética facial, harmonização facial, toxina botulínica, preenchimento facial, full face',
                'contact_phone' => '(51) 99824-4379',
                'contact_whatsapp' => '5551998244379',
                'contact_address' => 'Av. Ipiranga, 40, sala 1512 - Praia de Belas, Porto Alegre - RS | CEP 90160-090'
            ];
        }
    }

    public function get(string $key, $default = null): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $res = $stmt->fetch();
            return $res ? $res['setting_value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function set(string $key, string $value): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        return $stmt->execute([$key, $value]);
    }
}
