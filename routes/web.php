<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\Auth;
use App\Middleware\CsrfGuard;
use App\Middleware\RateLimit;

/**
 * app.birlikde.biz marşrutları (müştəri + kuryer).
 * Faza 3+ marşrutları (sifariş, SSE) sonrakı mərhələlərdə əlavə olunacaq
 * (bax CLAUDE.md bölmə 7, Əlavə A).
 */

$router = new Router();

$router->get('/', function (Request $request) {
    Response::json(['status' => 'ok', 'app' => 'birlikde-app']);
});

$router->get('/healthz', function (Request $request) {
    Response::json(['status' => 'ok']);
});

$router->get('/csrf-token', function (Request $request) {
    Response::json(['csrf_token' => Csrf::token()]);
});

$router->post('/qeydiyyat', [AuthController::class, 'qeydiyyat'], [CsrfGuard::class]);
$router->post('/giris', [AuthController::class, 'giris'], [CsrfGuard::class, RateLimit::class]);
$router->post('/cixis', [AuthController::class, 'cixis'], [CsrfGuard::class, Auth::class]);

return $router;
