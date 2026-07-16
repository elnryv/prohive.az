<?php
declare(strict_types=1);

/**
 * Fayl-əsaslı sadə rate limiter (12.1): login (5/15dəq), qeydiyyat (3/saat/IP),
 * track (30/dəq/IP). v1 miqyasında ayrıca cədvəl/Redis tələb olunmur.
 */
final class RateLimit
{
    private const DIR = APP_ROOT . '/storage/tmp/ratelimit';

    /** true = icazə verildi (cəhd qeydə alındı), false = limit aşılıb */
    public static function attempt(string $bucket, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }
        $key = preg_replace('/[^a-f0-9]/', '', sha1($bucket . '|' . $identifier));
        $file = self::DIR . '/' . $key . '.json';

        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return true;
        }
        flock($fp, LOCK_EX);

        $raw = stream_get_contents($fp);
        $timestamps = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        $now = time();
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn ($t) => is_int($t) && ($now - $t) < $windowSeconds
        ));

        $allowed = count($timestamps) < $maxAttempts;
        if ($allowed) {
            $timestamps[] = $now;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($timestamps));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $allowed;
    }

    /** Yalnız oxuyur, qeydə almır — login kimi "yalnız uğursuz cəhdlər sayılsın" ssenariləri üçün */
    public static function tooMany(string $bucket, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $timestamps = self::read($bucket, $identifier, $windowSeconds);
        return count($timestamps) >= $maxAttempts;
    }

    /** Bir cəhdi qeydə alır (məs. uğursuz login) */
    public static function hit(string $bucket, string $identifier, int $windowSeconds): void
    {
        self::write($bucket, $identifier, $windowSeconds, true);
    }

    /** Bucket üçün bütün cəhd tarixçəsini sıfırlayır (məs. uğurlu logindən sonra) */
    public static function clear(string $bucket, string $identifier): void
    {
        $file = self::file($bucket, $identifier);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function file(string $bucket, string $identifier): string
    {
        $key = preg_replace('/[^a-f0-9]/', '', sha1($bucket . '|' . $identifier));
        return self::DIR . '/' . $key . '.json';
    }

    /** @return int[] */
    private static function read(string $bucket, string $identifier, int $windowSeconds): array
    {
        $file = self::file($bucket, $identifier);
        if (!is_file($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        $timestamps = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        $now = time();
        return array_values(array_filter(
            $timestamps,
            static fn ($t) => is_int($t) && ($now - $t) < $windowSeconds
        ));
    }

    private static function write(string $bucket, string $identifier, int $windowSeconds, bool $append): void
    {
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }
        $file = self::file($bucket, $identifier);

        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return;
        }
        flock($fp, LOCK_EX);

        $raw = stream_get_contents($fp);
        $timestamps = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        $now = time();
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn ($t) => is_int($t) && ($now - $t) < $windowSeconds
        ));
        if ($append) {
            $timestamps[] = $now;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($timestamps));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
