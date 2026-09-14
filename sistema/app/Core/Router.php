<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupMiddlewares = [];

    public function add(string $method, string $path, mixed $handler, array $middlewares = []): void
    {
        $allMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        
        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => rtrim($path, '/') ?: '/',
            'handler'     => $handler,
            'middlewares' => $allMiddlewares,
        ];
    }

    public function get(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    public function options(string $path, mixed $handler, array $middlewares = []): void
    {
        $this->add('OPTIONS', $path, $handler, $middlewares);
    }

    public function group(array $middlewares, callable $callback): void
    {
        $previous = $this->groupMiddlewares;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        $callback($this);
        $this->groupMiddlewares = $previous;
    }

    public function dispatch(Request $request): void
    {
        $requestMethod = $request->getMethod();
        $requestPath = rtrim($request->getPath(), '/') ?: '/';

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $routePattern = "#^" . $routePattern . "$#";

            if (preg_match($routePattern, $requestPath, $matches)) {
                if ($route['method'] !== $requestMethod) {
                    $allowedMethods[] = $route['method'];
                    continue;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);

                // Run middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $this->runMiddleware($middleware, $request);
                }

                // Execute handler
                $this->executeHandler($route['handler'], $request);
                return;
            }
        }

        if ($requestPath === '/api' || str_starts_with($requestPath, '/api/')) {
            if ($allowedMethods !== []) {
                $methods = implode(', ', array_values(array_unique($allowedMethods)));
                header("Allow: {$methods}");
                Response::json(['message' => "Método não permitido: [{$requestMethod}] {$requestPath}"], 405);
            }

            Response::json(['message' => "Rota não encontrada: [{$requestMethod}] {$requestPath}"], 404);
            return;
        }

        // For non-API routes, let index.php handle the SPA HTML fallback
    }

    private function runMiddleware(string|callable $middleware, Request $request): void
    {
        if (is_callable($middleware)) {
            $middleware($request);
            return;
        }

        $param = null;
        if (str_contains($middleware, ':')) {
            [$middlewareName, $param] = explode(':', $middleware, 2);
        } else {
            $middlewareName = $middleware;
        }

        $classMap = [
            'auth'       => \App\Middlewares\AuthMiddleware::class,
            'role'       => \App\Middlewares\RoleMiddleware::class,
            'rate_limit' => \App\Middlewares\RateLimitMiddleware::class,
            'cors'       => \App\Middlewares\CorsMiddleware::class,
        ];

        if (isset($classMap[$middlewareName])) {
            $instance = new $classMap[$middlewareName]();
            if ($param !== null) {
                $instance->handle($request, $param);
            } else {
                $instance->handle($request);
            }
        }
    }

    private function executeHandler(mixed $handler, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            $controller = new $controllerClass();
            $controller->$method($request);
            return;
        }

        Response::json(['message' => 'Handler de rota inválido'], 500);
    }
}
