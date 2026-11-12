<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Config;
use PDO;

/** Thin PDO gateway. Timestamps always come from the Clock, never from SQL. */
final class Db
{
    public function __construct(public readonly PDO $pdo, public readonly string $driver)
    {
    }

    public static function connect(Config $config): self
    {
        $driver = $config->get('DB_CONNECTION', 'sqlite');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($driver === 'sqlite') {
            $path = $config->get('DB_DATABASE', 'database/database.sqlite');
            if (!str_starts_with($path, '/') && preg_match('/^[A-Za-z]:\\\\/', $path) !== 1) {
                $path = dirname(__DIR__, 3) . '/' . ltrim($path, '/');
            }
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');

            return new self($pdo, 'sqlite');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config->get('DB_HOST', '127.0.0.1'),
            $config->get('DB_PORT', '3306'),
            $config->get('DB_DATABASE', 'nuva'),
        );
        $pdo = new PDO($dsn, $config->get('DB_USERNAME', 'root'), $config->get('DB_PASSWORD', ''), $options + [PDO::ATTR_EMULATE_PREPARES => false]);
        // Column defaults (CURRENT_TIMESTAMP) follow the clinic's timezone too.
        $pdo->exec("SET time_zone = '" . (new \DateTimeImmutable('now', new \DateTimeZone($config->timezone())))->format('P') . "'");

        return new self($pdo, 'mysql');
    }

    public static function ts(?\DateTimeImmutable $at): ?string
    {
        return $at?->format('Y-m-d H:i:s');
    }

    /** @return list<array<string, mixed>> */
    public function all(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function one(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        $statement->closeCursor();

        return $row === false ? null : $row;
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->pdo->prepare($sql)->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }
}
