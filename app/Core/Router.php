<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(array $middleware, callable $callback): void
    {
        $this->groupStack[] = $middleware;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = rawurldecode($value);
                }
            }
            $this->run($route, $request, $params);
            return;
        }
        abort(404);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $inherited = [];
        foreach ($this->groupStack as $group) {
            $inherited = array_merge($inherited, $group);
        }
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => array_merge($inherited, $middleware),
            'regex' => $this->compile($path),
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path) ?? $path;
        return '#^' . $pattern . '$#';
    }

    private function run(array $route, Request $request, array $params): void
    {
        $destination = function (Request $req) use ($route, $params): void {
            $handler = $route['handler'];
            if (is_callable($handler)) {
                $handler($req, $params);
                return;
            }
            [$class, $method] = $handler;
            (new $class())->{$method}($req, $params);
        };
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            fn (callable $next, string $middleware): callable => function (Request $req) use ($middleware, $next): void {
                $this->invokeMiddleware($middleware, $req, $next);
            },
            $destination
        );
        $pipeline($request);
    }

    private function invokeMiddleware(string $middleware, Request $request, callable $next): void
    {
        [$name, $args] = array_pad(explode(':', $middleware, 2), 2, '');
        $map = [
            'auth' => \App\Http\Middleware\AuthMiddleware::class,
            'guest' => \App\Http\Middleware\GuestMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'can' => \App\Http\Middleware\PermissionMiddleware::class,
        ];
        if (!isset($map[$name])) {
            throw new \RuntimeException('Unknown middleware: ' . $name);
        }
        (new $map[$name]())->handle($request, $next, $args === '' ? [] : explode(',', $args));
    }
}
