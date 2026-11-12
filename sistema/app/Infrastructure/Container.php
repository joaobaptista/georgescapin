<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Minimal dependency-injection container: explicit factories for interfaces
 * and configuration, constructor autowiring for everything else. One shared
 * instance per id for the lifetime of the request.
 */
final class Container
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /** @param class-string $concrete */
    public function bind(string $abstract, string $concrete): void
    {
        $this->set($abstract, fn (self $c): object => $c->get($concrete));
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        return $this->instances[$id] ??= isset($this->factories[$id])
            ? ($this->factories[$id])($this)
            : $this->autowire($id);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new \LogicException("Nenhuma ligação registrada para {$class}.");
        }
        $constructor = (new \ReflectionClass($class))->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new \LogicException("Não é possível resolver \${$parameter->getName()} de {$class}.");
            }
        }

        return new $class(...$arguments);
    }
}
