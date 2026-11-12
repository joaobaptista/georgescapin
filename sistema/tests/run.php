<?php

declare(strict_types=1);

/**
 * Unit tests of the domain and application layers (no database, no HTTP).
 *
 *   php tests/run.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Support/TestCase.php';
require __DIR__ . '/Support/Fakes.php';

$passed = 0;
$failed = 0;
foreach (glob(__DIR__ . '/Unit/*Test.php') as $file) {
    require_once $file;
    $class = 'Tests\\Unit\\' . basename($file, '.php');
    foreach (get_class_methods($class) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }
        $label = basename($file, 'Test.php') . ' › ' . $method;
        try {
            (new $class())->{$method}();
            $passed++;
            echo "PASS {$label}\n";
        } catch (Throwable $error) {
            $failed++;
            echo "FAIL {$label} — " . $error::class . ": {$error->getMessage()}\n";
        }
    }
}

echo "\nResult: {$passed}/" . ($passed + $failed) . ' passed' . ($failed ? ", {$failed} failed.\n" : ".\n");
exit($failed ? 1 : 0);
