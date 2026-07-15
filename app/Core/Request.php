<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $jsonBody;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $_POST;
        $this->server = $_SERVER;
        $this->jsonBody = [];

        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '[]', true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
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

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->jsonBody[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->jsonBody);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isJson(): bool
    {
        return str_contains($this->server['CONTENT_TYPE'] ?? '', 'application/json');
    }

    /**
     * @return array{name:string, type:string, tmp_name:string, error:int, size:int}|null
     */
    public function file(string $key): ?array
    {
        return isset($_FILES[$key]) && is_array($_FILES[$key]) ? $_FILES[$key] : null;
    }
}
