<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Request;
use App\Core\Session;

// PHP built-in dev server (php -S) ilə statik faylları birbaşa yayımlamaq üçün
// (production-da bunu artıq nginx `try_files` edir — bax deploy/nginx/*.conf).
if (PHP_SAPI === 'cli-server') {
    $staticPath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($staticPath !== __DIR__ . '/' && is_file($staticPath)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

Session::start(Env::get('ADMIN_SESSION_NAME', 'birlikde_admin_session'));

/** @var \App\Core\Router $router */
$router = require dirname(__DIR__) . '/routes/admin.php';

$request = new Request();
$router->dispatch($request);
