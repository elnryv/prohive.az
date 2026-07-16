<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\Site\HomeController;

$router = new Router();

$router->get('/', function () {
    (new HomeController())->index();
});

// FAZA 1-dən etibarən əlavə olunacaq: /qeydiyyat, /giris, /cixis ...
// FAZA 2-dən: /musteri/*, /surucu/lent, /e/{code} ...

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
