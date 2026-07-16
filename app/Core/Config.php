<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static ?array $data = null;

    public static function load(): void
    {
        if (self::$data !== null) {
            return;
        }
        $file = dirname(__DIR__, 2) . '/config.php';
        self::$data = require $file;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        $segments = explode('.', $key);
        $value = self::$data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
