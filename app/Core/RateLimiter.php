<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Fayl-əsaslı sadə rate-limiter (giriş, SSE, ödəniş üçün — bax CLAUDE.md bölmə 6).
 * Framework/əlavə asılılıq yoxdur, storage/cache/ratelimit/ altında JSON saxlanır.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $data = self::read($key);

        if ($data === null || $data['reset_at'] < time()) {
            return false;
        }

        return $data['count'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $data = self::read($key);
        $now = time();

        if ($data === null || $data['reset_at'] < $now) {
            $data = ['count' => 0, 'reset_at' => $now + $decaySeconds];
        }

        $data['count']++;
        file_put_contents(self::path($key), json_encode($data), LOCK_EX);
    }

    public static function clear(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private static function read(string $key): ?array
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        return is_array($json) ? $json : null;
    }

    private static function path(string $key): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/ratelimit';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/' . hash('sha256', $key) . '.json';
    }
}
