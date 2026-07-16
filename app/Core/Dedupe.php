<?php
declare(strict_types=1);

/**
 * Sadə fayl-kilidli dedupe (4.9): IP+house hash-i TTL müddətində /tmp faylında.
 * views_total / wa_clicks_total kimi sayğacların təkrar-sayılmasının qarşısını alır.
 */
final class Dedupe
{
    private const DIR = APP_ROOT . '/storage/tmp';

    /** true qaytarırsa — bu hadisə hələ sayılmayıb, indi sayıla bilər (marker yazılır) */
    public static function shouldCount(string $key, int $ttlSeconds): bool
    {
        if (!is_dir(self::DIR)) {
            mkdir(self::DIR, 0775, true);
        }
        $file = self::DIR . '/' . preg_replace('/[^a-f0-9]/', '', sha1($key)) . '.marker';

        // mtime fopen('c+')-dan ƏVVƏL oxunmalıdır — 'c+' fayl yoxdursa onu yaradır,
        // əks halda ilk ziyarət də yanlışlıqla "artıq sayılıb" kimi görünür.
        $existedBefore = is_file($file);
        $mtimeBefore = $existedBefore ? (filemtime($file) ?: 0) : 0;

        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return true;
        }
        flock($fp, LOCK_EX);

        $fresh = $existedBefore && (time() - $mtimeBefore < $ttlSeconds);

        if (!$fresh) {
            ftruncate($fp, 0);
            fwrite($fp, (string) time());
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return !$fresh;
    }

    public static function looksLikeBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|monitor|curl|wget|python-requests/i',
            $userAgent
        );
    }
}
