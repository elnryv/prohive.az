<?php
declare(strict_types=1);

final class SettingsRepository
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $v = self::get($key, (string) $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $v = self::get($key, (string) $default);
        return is_numeric($v) ? (float) $v : $default;
    }

    public static function set(string $key, string $value): void
    {
        DB::query(
            'INSERT INTO settings (skey, svalue) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE svalue = :v2',
            [':k' => $key, ':v' => $value, ':v2' => $value]
        );
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        foreach (DB::all('SELECT skey, svalue FROM settings') as $row) {
            self::$cache[$row['skey']] = $row['svalue'];
        }
    }
}
