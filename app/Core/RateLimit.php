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

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
