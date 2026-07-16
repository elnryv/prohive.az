<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('APP_CONTEXT')) {
    define('APP_CONTEXT', 'site'); // 'site'|'admin' — public_admin/index.php 'admin' təyin edir
}

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// ================== AUTOLOAD ==================
spl_autoload_register(static function (string $class): void {
    $dirs = [
        APP_ROOT . '/app/Core/',
        APP_ROOT . '/app/Models/',
        APP_ROOT . '/app/Payments/',
        APP_ROOT . '/app/Controllers/Site/',
        APP_ROOT . '/app/Controllers/Owner/',
        APP_ROOT . '/app/Controllers/Admin/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// ================== SESSİYA ==================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name(APP_CONTEXT === 'admin' ? ADMIN_SESSION_NAME : SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================== DİL ==================
Lang::boot();
