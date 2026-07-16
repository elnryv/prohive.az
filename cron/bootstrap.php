<?php

declare(strict_types=1);

/** Cron skriptləri üçün yüngül bootstrap — sessiya/router/view yoxdur, yalnız Core sinifləri. */

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = substr($class, strlen('App\\'));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Core\Config;

Config::load();
date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Baku'));
