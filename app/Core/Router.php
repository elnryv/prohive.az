<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:mixed, middleware:array}> */
    private array $routes = [];

    public function get(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $middleware);
    }

    public function addRoute(string $method, string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/');
        $path = $path === '' ? '/' : $path;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            return $this->runMiddleware($route['middleware'], $request, function () use ($route, $request, $params) {
                return $this->callHandler($route['handler'], $request, $params);
            });
        }

        Response::notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        $pattern = rtrim($pattern, '/');
        $pattern = $pattern === '' ? '/' : $pattern;

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function runMiddleware(array $middleware, Request $request, callable $final): mixed
    {
        $chain = array_reduce(
            array_reverse($middleware),
            function (callable $next, string $middlewareClass) {
                return function (Request $request) use ($middlewareClass, $next) {
                    /** @var MiddlewareInterface $instance */
                    $instance = new $middlewareClass();
                    return $instance->handle($request, $next);
                };
            },
            $final
        );

        return $chain($request);
    }

    private function callHandler(mixed $handler, Request $request, array $params): mixed
    {
        if ($handler instanceof \Closure) {
            return $handler($request, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            return $controller->$method($request, $params);
        }

        throw new \RuntimeException('Yanlış marşrut handler tipi.');
    }
}
