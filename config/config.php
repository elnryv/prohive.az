<?php
declare(strict_types=1);

/**
 * .env faylını parse edir və $_ENV / getenv() vasitəsilə əlçatan edir.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        if ($key === '') {
            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

load_env(dirname(__DIR__) . '/.env');

/**
 * .env dəyərini oxuyan köməkçi funksiya.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}

/**
 * Tətbiq konfiqurasiyasını qaytarır (bir dəfə hesablanır, sonra yaddaşda saxlanılır).
 * require_once ilə çağırıldıqda faylın "return" dəyəri yalnız ilk dəfə əlçatan olduğu üçün
 * konfiqurasiyanı funksiya vasitəsilə qaytarırıq.
 */
function config(): array
{
    static $config = null;

    if ($config === null) {
        $config = [
            'app' => [
                'url' => env('APP_URL', 'http://localhost'),
                'env' => env('APP_ENV', 'production'),
            ],
            'db' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'name' => env('DB_NAME', 'birlikde_bots'),
                'user' => env('DB_USER', 'root'),
                'pass' => env('DB_PASS', ''),
            ],
            'telegram' => [
                'api' => env('TELEGRAM_API', 'https://api.telegram.org'),
            ],
            'session' => [
                'name' => env('SESSION_NAME', 'birlikde_admin'),
            ],
        ];
    }

    return $config;
}
