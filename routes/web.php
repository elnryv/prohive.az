<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BannerImageController;
use App\Controllers\KuryeController;
use App\Controllers\OdenisController;
use App\Controllers\SifarisController;
use App\Controllers\SseController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\Auth;
use App\Middleware\CsrfGuard;
use App\Middleware\KuryeGuard;
use App\Middleware\MusteriGuard;
use App\Middleware\RateLimit;

/**
 * app.birlikde.biz marşrutları (müştəri + kuryer) — bax CLAUDE.md bölmə 7, Əlavə A.
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

// Müştəri — sifariş (bax bölmə 7.1)
$router->post('/sifaris/yarat', [SifarisController::class, 'yarat'], [Auth::class, MusteriGuard::class, CsrfGuard::class]);
$router->post('/sifaris/{id}/legv', [SifarisController::class, 'legv'], [Auth::class, MusteriGuard::class, CsrfGuard::class]);
$router->get('/sifaris/tarixce', [SifarisController::class, 'tarixce'], [Auth::class, MusteriGuard::class]);

// Kuryer — canlı lövhə, götürmə, tamamlama, onlayn/ərazi (bax bölmə 6.2, 6.3, 7.2)
$router->get('/sse/lovhe', [SseController::class, 'lovhe'], [Auth::class, KuryeGuard::class]);
$router->post('/sifaris/{id}/gotur', [SifarisController::class, 'gotur'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->post('/sifaris/{id}/tamamla', [SifarisController::class, 'tamamla'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->post('/kurye/onlayn', [KuryeController::class, 'onlayn'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->post('/kurye/bolgeler', [KuryeController::class, 'bolgeler'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);

// Banner şəkli göstərmə (bax bölmə 8.4, admin panelində yüklənir, burada yayımlanır)
$router->get('/banner-sekil/{fayl}', [BannerImageController::class, 'goster']);

// Abunə ödənişi (bax bölmə 9.1)
$router->post('/odenis/basla', [OdenisController::class, 'basla'], [Auth::class, KuryeGuard::class, CsrfGuard::class, RateLimit::class]);
$router->post('/webhook/odenis', [OdenisController::class, 'webhook'], [RateLimit::class]);

return $router;
