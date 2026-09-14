<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
            
            try {
                if ($driver === 'sqlite') {
                    $configuredPath = $_ENV['DB_DATABASE'] ?? 'database/database.sqlite';
                    if (str_starts_with($configuredPath, '/') || preg_match('/^[A-Za-z]:\\\\/', $configuredPath)) {
                        $dbPath = $configuredPath;
                    } else {
                        $dbPath = dirname(__DIR__, 2) . '/' . ltrim($configuredPath, '/');
                    }
                    
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!file_exists($dbPath)) {
                        touch($dbPath);
                    }
                    
                    self::$instance = new PDO("sqlite:" . $dbPath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec("PRAGMA foreign_keys = ON;");
                } else {
                    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                    $port = $_ENV['DB_PORT'] ?? '3306';
                    $db   = $_ENV['DB_DATABASE'] ?? 'nuva';
                    $user = $_ENV['DB_USERNAME'] ?? 'root';
                    $pass = $_ENV['DB_PASSWORD'] ?? '';

                    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                    self::$instance->exec("SET time_zone = '" . date('P') . "';");
                }
            } catch (PDOException $e) {
                throw new \RuntimeException("Database Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';
        if ($driver === 'mysql') {
            $sql = str_replace("datetime('now')", "NOW()", $sql);
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function resetConnection(): void
    {
        self::$instance = null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::getConnection()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }
}
