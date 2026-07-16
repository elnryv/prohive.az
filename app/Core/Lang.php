<?php
declare(strict_types=1);

final class Lang
{
    private static string $current = DEFAULT_LANG;
    /** @var array<string, string> */
    private static array $dict = [];

    public static function boot(): void
    {
        $requested = $_GET['lang'] ?? $_COOKIE['lang'] ?? DEFAULT_LANG;
        $lang = in_array($requested, SUPPORTED_LANGS, true) ? $requested : DEFAULT_LANG;

        if (isset($_GET['lang']) && $lang !== ($_COOKIE['lang'] ?? null)) {
            setcookie('lang', $lang, [
                'expires' => time() + 60 * 60 * 24 * 365,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        self::$current = $lang;
        $file = APP_ROOT . '/app/lang/' . $lang . '.php';
        self::$dict = is_file($file) ? require $file : [];
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function t(string $key, ?string $fallback = null): string
    {
        return self::$dict[$key] ?? $fallback ?? $key;
    }

    /** sprintf formatlı açar üçün: Lang::tf('listing.house_count', 5) */
    public static function tf(string $key, mixed ...$args): string
    {
        return vsprintf(self::t($key), $args);
    }
}
