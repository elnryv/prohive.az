<?php

declare(strict_types=1);

/**
 * Bütün giriş nöqtələri (public/index.php, public/admin.php, cron/*) bu faylı
 * daxil edir. Framework yoxdur — sadə PSR-4-bənzər avtoloader + .env yüklənməsi.
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

// Yalnız real Web Push göndərmə üçün minishlink/web-push (bax composer.json,
// PushService.php) — layihənin qalan hissəsi asılılıqsız native PHP-dir.
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

use App\Core\Env;
use App\Core\ErrorHandler;

Env::load(__DIR__ . '/.env');
ErrorHandler::register();

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Baku'));

return require __DIR__ . '/config/config.php';
