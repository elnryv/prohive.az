<?php

declare(strict_types=1);

/**
 * Minimal PSR-4-bənzər autoloader — Composer yoxdur. App\Foo\Bar -> app/Foo/Bar.php
 */

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

require __DIR__ . '/helpers.php';

use App\Core\Config;
use App\Core\Lang;
use App\Core\View;

Config::load();

date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Baku'));

$env = Config::get('app.env', 'production');
if ($env === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
session_name((string) Config::get('session.name', 'yuk_sess'));
session_start();

Lang::boot();
if (isset($_GET['lang'])) {
    Lang::set((string) $_GET['lang']);
}
View::boot(dirname(__DIR__) . '/app/views');
