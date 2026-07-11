<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

/**
 * app.birlikde.biz marşrutları (müştəri + kuryer).
 * Controller-lər Faza 2+ mərhələlərində əlavə olunacaq (bax CLAUDE.md bölmə 7, Əlavə A).
 * Faza 0 üçün yalnız bootstrap-ın işlədiyini göstərən sağlamlıq yoxlaması var.
 */

$router = new Router();

$router->get('/', function (Request $request) {
    Response::json(['status' => 'ok', 'app' => 'birlikde-app']);
});

$router->get('/healthz', function (Request $request) {
    Response::json(['status' => 'ok']);
});

return $router;
