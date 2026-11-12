<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Shared\Transaction;

final class PdoTransaction implements Transaction
{
    public function __construct(private readonly Db $db)
    {
    }

    public function run(callable $work): mixed
    {
        // Nested units join the outer transaction.
        if ($this->db->pdo->inTransaction()) {
            return $work();
        }

        $this->db->pdo->beginTransaction();
        try {
            $result = $work();
            $this->db->pdo->commit();

            return $result;
        } catch (\Throwable $error) {
            $this->db->pdo->rollBack();
            throw $error;
        }
    }
}
