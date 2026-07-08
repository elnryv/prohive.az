<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, keys: array, handler: mixed, auth: bool}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, callable|array $handler, bool $auth = false): void
    {
        $this->addRoute('GET', $path, $handler, $auth);
    }

    public function post(string $path, callable|array $handler, bool $auth = false): void
    {
        $this->addRoute('POST', $path, $handler, $auth);
    }

    private function addRoute(string $method, string $path, callable|array $handler, bool $auth): void
    {
        $keys = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', static function (array $m) use (&$keys): string {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'keys' => $keys,
            'handler' => $handler,
            'auth' => $auth,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = array_combine($route['keys'], $matches) ?: [];

                if ($route['auth'] && !Auth::check()) {
                    Response::redirect('/login');
                    return;
                }

                $this->invoke($route['handler'], $params, $request);
                return;
            }
        }

        http_response_code(404);
        echo '404 - Səhifə tapılmadı';
    }

    private function invoke(callable|array $handler, array $params, Request $request): void
    {
        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $methodName], array_merge($params, ['request' => $request]));
            return;
        }

        call_user_func_array($handler, array_merge($params, ['request' => $request]));
    }
}
