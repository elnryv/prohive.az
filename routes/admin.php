<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

/**
 * appadmin.birlikde.biz marşrutları (izolyasiya olunmuş admin paneli).
 * Controller-lər Faza 4-də əlavə olunacaq (bax CLAUDE.md bölmə 8).
 */

$router = new Router();

$router->get('/', function (Request $request) {
    Response::json(['status' => 'ok', 'app' => 'birlikde-admin']);
});

$router->get('/healthz', function (Request $request) {
    Response::json(['status' => 'ok']);
});

return $router;
