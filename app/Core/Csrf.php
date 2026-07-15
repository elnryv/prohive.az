<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }

        return (string) Session::get(self::SESSION_KEY);
    }

    public static function verify(?string $token): bool
    {
        $expected = Session::get(self::SESSION_KEY);

        if (!is_string($expected) || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }
}
