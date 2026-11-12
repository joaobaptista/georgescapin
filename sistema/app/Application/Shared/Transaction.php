<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Runs a unit of work atomically: everything is saved, or nothing is. */
interface Transaction
{
    /**
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function run(callable $work): mixed;
}
