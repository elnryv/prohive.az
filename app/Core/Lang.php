<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Çoxdilli struktur — bax CLAUDE.md bölmə 3.5. Dil faylları
 * public/assets/lang/{az,ru,en}.json-da saxlanır. Default: Azərbaycan.
 */
final class Lang
{
    private const DILLER = ['az', 'ru', 'en'];

    private static string $dil = 'az';
    /** @var array<string, array<string, string>> */
    private static array $strings = [];

    /**
     * Sorğu parametri (?dil=) → sessiya → istifadəçinin profil dili → default 'az'
     * sırası ilə cari dili müəyyən edir (bax bölmə 3.5).
     */
    public static function resolve(Request $request, ?string $userDil = null): void
    {
        $sorgu = $request->query('dil');
        if (is_string($sorgu) && in_array($sorgu, self::DILLER, true)) {
            Session::set('dil', $sorgu);
            self::set($sorgu);
            return;
        }

        $sessiyaDili = Session::get('dil');
        if (is_string($sessiyaDili) && in_array($sessiyaDili, self::DILLER, true)) {
            self::set($sessiyaDili);
            return;
        }

        if ($userDil !== null && in_array($userDil, self::DILLER, true)) {
            self::set($userDil);
            return;
        }

        self::set('az');
    }

    public static function set(string $dil): void
    {
        self::$dil = in_array($dil, self::DILLER, true) ? $dil : 'az';
    }

    public static function current(): string
    {
        return self::$dil;
    }

    public static function get(string $key): string
    {
        if (!isset(self::$strings[self::$dil])) {
            self::$strings[self::$dil] = self::load(self::$dil);
        }

        return self::$strings[self::$dil][$key] ?? $key;
    }

    private static function load(string $dil): array
    {
        $path = dirname(__DIR__, 2) . "/public/assets/lang/{$dil}.json";
        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : [];
    }
}
