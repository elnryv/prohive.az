<?php
declare(strict_types=1);

/**
 * Composer yoxdur — sadə PSR-4 uyğun avtoloader.
 * "App\Core\Database" -> app/Core/Database.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require_once __DIR__ . '/../config/config.php';

if (!function_exists('e')) {
    /**
     * XSS qarşısını almaq üçün HTML escape köməkçisi.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
