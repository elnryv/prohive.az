<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';

const SETTINGS_APCU_KEY = 'ybb_settings_v1';

function settings_all(): array
{
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch(SETTINGS_APCU_KEY, $ok);
        if ($ok) {
            return $cached;
        }
    }

    static $requestCache = null;
    if ($requestCache !== null) {
        return $requestCache;
    }

    $rows = db()->query('SELECT skey, svalue FROM settings')->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[$row['skey']] = $row['svalue'];
    }

    $requestCache = $map;
    if (function_exists('apcu_store')) {
        apcu_store(SETTINGS_APCU_KEY, $map, 60);
    }

    return $map;
}

function settings_get(string $key, ?string $default = null): ?string
{
    $all = settings_all();
    return $all[$key] ?? $default;
}

function settings_get_int(string $key, int $default): int
{
    $value = settings_get($key);
    return $value === null ? $default : (int) $value;
}

function settings_get_bool(string $key, bool $default): bool
{
    $value = settings_get($key);
    return $value === null ? $default : $value === '1';
}

function maintenance_guard(): void
{
    if (settings_get_bool('maintenance_mode', false)) {
        json_error('MAINTENANCE', 'Sistem hazırda texniki baxımdadır. Bir az sonra yenidən cəhd edin.', 503);
    }
}

function settings_set(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (skey, svalue) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE svalue = :value2'
    );
    $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);

    if (function_exists('apcu_delete')) {
        apcu_delete(SETTINGS_APCU_KEY);
    }
}
