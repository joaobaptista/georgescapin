<?php

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../../config/database.php';
            
            try {
                $driver = $config['driver'] ?? 'mysql';

                if ($driver === 'sqlite') {
                    $dbPath = $config['database'];
                    self::$instance = new PDO("sqlite:" . $dbPath, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]);
                    self::$instance->exec('PRAGMA foreign_keys = ON;');
                } elseif ($driver === 'pgsql') {
                    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                    self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]);
                } else {
                    // MySQL Padrão
                    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                    self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]);
                }
            } catch (PDOException $e) {
                die("Erro na conexão com o banco de dados MySQL: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
