<?php

declare(strict_types=1);

namespace App\Http;

/** Immutable HTTP response, emitted once by public/index.php. */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status = 200,
        private readonly array $headers = [],
        public readonly string $body = '',
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** @param array<string, list<string>> $errors */
    public static function error(string $message, int $status, array $errors = []): self
    {
        return self::json($errors === [] ? ['message' => $message] : ['message' => $message, 'errors' => $errors], $status);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public static function html(string $html): self
    {
        return new self(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->status, [$name => $value] + $this->headers, $this->body);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function send(bool $withBody = true): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        if ($withBody && $this->status !== 204) {
            echo $this->body;
        }
    }
}
