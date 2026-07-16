<?php

declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static array $strings = [];
    private static string $current = 'az';

    public static function boot(?string $requested = null): void
    {
        $supported = Config::get('app.supported_langs', ['az', 'ru', 'en']);
        $lang = $requested ?? self::detect($supported);
        if (!in_array($lang, $supported, true)) {
            $lang = Config::get('app.default_lang', 'az');
        }
        self::$current = $lang;
        self::$strings = require dirname(__DIR__) . "/lang/{$lang}.php";
    }

    private static function detect(array $supported): string
    {
        if (isset($_COOKIE['yuk_lang']) && in_array($_COOKIE['yuk_lang'], $supported, true)) {
            return $_COOKIE['yuk_lang'];
        }
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach (explode(',', $header) as $part) {
            $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
            if (in_array($code, $supported, true)) {
                return $code;
            }
        }
        return Config::get('app.default_lang', 'az');
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function set(string $lang): void
    {
        $supported = Config::get('app.supported_langs', ['az', 'ru', 'en']);
        if (!in_array($lang, $supported, true)) {
            return;
        }
        self::$current = $lang;
        self::$strings = require dirname(__DIR__) . "/lang/{$lang}.php";
        setcookie('yuk_lang', $lang, [
            'expires' => time() + 86400 * 365,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
    }

    public static function t(string $key, array $params = []): string
    {
        $segments = explode('.', $key);
        $value = self::$strings;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $key;
            }
            $value = $value[$segment];
        }
        if (!is_string($value)) {
            return $key;
        }
        if ($params !== []) {
            $replace = [];
            foreach ($params as $k => $v) {
                $replace['{' . $k . '}'] = (string) $v;
            }
            $value = strtr($value, $replace);
        }
        return $value;
    }

    /** Cədvəldəki üçdilli sahədən (name_az/name_ru/name_en) cari dilə uyğun dəyər. */
    public static function field(array $row, string $base): string
    {
        $key = $base . '_' . self::$current;
        return (string) ($row[$key] ?? $row[$base . '_az'] ?? '');
    }
}
