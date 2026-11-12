<?php

declare(strict_types=1);

namespace App\Infrastructure;

/** Typed access to environment configuration (.env or real environment). */
final class Config
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null || $value === '' ? $default : (string) $value;
    }

    public function timezone(): string
    {
        return $this->get('APP_TIMEZONE', 'America/Sao_Paulo');
    }

    public function isProduction(): bool
    {
        return strtolower((string) $this->get('APP_ENV', '')) === 'production';
    }

    public function debug(): bool
    {
        return filter_var($this->get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
    }

    public function appUrl(): string
    {
        return rtrim($this->get('APP_URL', 'http://127.0.0.1:8000'), '/');
    }

    public function int(string $key, int $default): int
    {
        return (int) $this->get($key, (string) $default);
    }

    /** @return list<string> proxies whose X-Forwarded-* headers are trusted */
    public function trustedProxies(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->get('TRUSTED_PROXIES', '')))));
    }
}
