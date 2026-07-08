<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $get;
    private array $post;
    private array $server;
    private ?array $json = null;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path === null || $path === '' ? '/' : $path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->get[$key] ?? $default);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->post !== [] ? $this->post : $this->jsonBody();
        return $this->sanitize($body[$key] ?? $default);
    }

    public function all(): array
    {
        $body = $this->post !== [] ? $this->post : $this->jsonBody();
        return $this->sanitize($body);
    }

    public function jsonBody(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $raw = file_get_contents('php://input');
        $decoded = $raw === false || $raw === '' ? [] : json_decode($raw, true);

        $this->json = is_array($decoded) ? $decoded : [];
        return $this->json;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }
}
