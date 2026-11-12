<?php

declare(strict_types=1);

namespace App\Http;

use App\Application\Shared\Actor;

final class Request
{
    private array $routeParams = [];

    public ?Actor $actor = null;

    public ?string $clientIp = null;

    public bool $https = false;

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'AUTHORIZATION'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }
        if (!isset($headers['authorization']) && function_exists('apache_request_headers')) {
            $apache = array_change_key_case(apache_request_headers());
            if (isset($apache['authorization'])) {
                $headers['authorization'] = $apache['authorization'];
            }
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $body = [];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            $body = is_array($json) ? $json : $_POST;
        }

        return new self(
            $method,
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            $_GET,
            $body,
            $headers,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isApi(): bool
    {
        return $this->path === '/api' || str_starts_with($this->path, '/api/');
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function cookie(string $name): ?string
    {
        foreach (explode(';', (string) $this->header('cookie')) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, null);
            if ($key === $name && $value !== null) {
                return urldecode($value);
            }
        }

        return null;
    }

    public function bearerToken(): ?string
    {
        return preg_match('/Bearer\s+(\S+)/i', (string) $this->header('authorization'), $m) === 1 ? $m[1] : null;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** Body fields override query-string fields. */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function withRouteParams(array $params): self
    {
        $clone = clone $this;
        $clone->routeParams = $params;

        return $clone;
    }

    public function routeInt(string $name): int
    {
        return (int) ($this->routeParams[$name] ?? 0);
    }

    public function actor(): Actor
    {
        return $this->actor ?? throw new \LogicException('Rota protegida sem o middleware de autenticação.');
    }
}
