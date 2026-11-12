<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Middleware;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\Throttle;
use App\Infrastructure\Container;

final class Router
{
    private const MIDDLEWARE = [
        'auth' => Authenticate::class,
        'role' => RequireRole::class,
        'rate_limit' => Throttle::class,
    ];

    /** @var list<array{method: string, pattern: string, handler: array{class-string, string}, middleware: list<string>}> */
    private array $routes = [];

    /** @var list<string> */
    private array $groupMiddleware = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $path, array $handler, array $middleware = []): void { $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, array $handler, array $middleware = []): void { $this->add('POST', $path, $handler, $middleware); }
    public function put(string $path, array $handler, array $middleware = []): void { $this->add('PUT', $path, $handler, $middleware); }
    public function patch(string $path, array $handler, array $middleware = []): void { $this->add('PATCH', $path, $handler, $middleware); }
    public function delete(string $path, array $handler, array $middleware = []): void { $this->add('DELETE', $path, $handler, $middleware); }

    /** @param callable(self): void $routes */
    public function group(array $middleware, callable $routes): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = [...$previous, ...$middleware];
        $routes($this);
        $this->groupMiddleware = $previous;
    }

    public function dispatch(Request $request): Response
    {
        $path = rtrim($request->path(), '/') ?: '/';
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $request->method()) {
                $allowed[] = $route['method'];
                continue;
            }

            return $this->run($route, $request->withRouteParams(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)));
        }

        if ($allowed !== []) {
            return Response::json(['message' => "Método não permitido: [{$request->method()}] {$path}"], 405)
                ->withHeader('Allow', implode(', ', array_unique($allowed)));
        }

        return Response::json(['message' => "Rota não encontrada: [{$request->method()}] {$path}"], 404);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $pattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'handler' => $handler, 'middleware' => [...$this->groupMiddleware, ...$middleware]];
    }

    private function run(array $route, Request $request): Response
    {
        [$class, $method] = $route['handler'];
        $pipeline = fn (Request $r): Response => $this->container->get($class)->{$method}($r);

        foreach (array_reverse($route['middleware']) as $spec) {
            [$name, $parameter] = array_pad(explode(':', $spec, 2), 2, null);
            /** @var Middleware $middleware */
            $middleware = $this->container->get(self::MIDDLEWARE[$name] ?? throw new \LogicException("Middleware desconhecido: {$name}"));
            $next = $pipeline;
            $pipeline = static fn (Request $r): Response => $middleware->process($r, $next, $parameter);
        }

        return $pipeline($request);
    }
}
