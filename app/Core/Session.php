<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        session_name(Env::get('SESSION_NAME', 'birlikde_session'));

        session_set_cookie_params([
            'lifetime' => Env::getInt('SESSION_LIFETIME', 0),
            'path' => '/',
            'domain' => '',
            'secure' => Env::getBool('SESSION_SECURE', true),
            'httponly' => true,
            'samesite' => Env::get('SESSION_SAMESITE', 'Lax'),
        ]);

        session_start();
        self::$started = true;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();
        self::$started = false;
    }

    /**
     * SSE üçün: sessiya kilidini buraxır ki, eyni istifadəçinin digər
     * sorğuları bloklanmasın (bax CLAUDE.md bölmə 5, SSE-6.2).
     */
    public static function writeClose(): void
    {
        session_write_close();
    }
}
