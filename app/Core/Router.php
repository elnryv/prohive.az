<?php
declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:callable}> */
    private array $routes = [];

    /** @var callable|null */
    private $notFoundHandler = null;

    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#u',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim((string) parse_url($uri, PHP_URL_PATH), '/');
        if ($path === '') {
            $path = '/';
        }
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn ($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        if ($this->notFoundHandler !== null) {
            ($this->notFoundHandler)();
            return;
        }
        $viewFile = APP_ROOT . '/app/views/site/404.php';
        if (is_file($viewFile)) {
            View::render('site/404');
        } else {
            echo '404 Not Found';
        }
    }
}
