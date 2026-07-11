<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Request;
use App\Core\Session;

require dirname(__DIR__) . '/bootstrap.php';

Session::start(Env::get('ADMIN_SESSION_NAME', 'birlikde_admin_session'));

/** @var \App\Core\Router $router */
$router = require dirname(__DIR__) . '/routes/admin.php';

$request = new Request();
$router->dispatch($request);
