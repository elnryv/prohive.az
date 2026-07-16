<?php

declare(strict_types=1);

namespace App\Core;

final class Settings
{
    private static ?array $cache = null;

    private static function loadAll(): array
    {
        if (self::$cache === null) {
            $stmt = DB::conn()->query('SELECT skey, svalue FROM settings');
            self::$cache = [];
            foreach ($stmt->fetchAll() as $row) {
                self::$cache[$row['skey']] = $row['svalue'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::loadAll();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = DB::conn()->prepare(
            'INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );
        $stmt->execute([$key, $value]);
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function refresh(): void
    {
        self::$cache = null;
    }
}
