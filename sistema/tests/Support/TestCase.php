<?php

declare(strict_types=1);

namespace Tests\Support;

abstract class TestCase
{
    protected static function assertTrue(bool $condition, string $message = 'esperava verdadeiro'): void
    {
        if (!$condition) {
            throw new AssertionFailed($message);
        }
    }

    protected static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed(trim("{$message} esperado " . var_export($expected, true) . ', obtido ' . var_export($actual, true)));
        }
    }

    /**
     * @param class-string<\Throwable> $class
     */
    protected static function assertThrows(string $class, callable $action, ?string $messageContains = null): void
    {
        try {
            $action();
        } catch (\Throwable $error) {
            if (!$error instanceof $class) {
                throw new AssertionFailed("esperava {$class}, obtido " . $error::class . ": {$error->getMessage()}");
            }
            if ($messageContains !== null && !str_contains($error->getMessage(), $messageContains)) {
                throw new AssertionFailed("mensagem \"{$error->getMessage()}\" não contém \"{$messageContains}\"");
            }

            return;
        }

        throw new AssertionFailed("esperava a exceção {$class}");
    }
}

final class AssertionFailed extends \RuntimeException
{
}
