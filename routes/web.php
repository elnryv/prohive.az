<?php

declare(strict_types=1);

use App\Controllers\AppPageController;
use App\Controllers\AuthController;
use App\Controllers\BannerImageController;
use App\Controllers\EraziController;
use App\Controllers\KuryeController;
use App\Controllers\KuryeSekilController;
use App\Controllers\LegalController;
use App\Controllers\OdenisController;
use App\Controllers\PushController;
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

// Kuryer — canlı lövhə, götürmə (= tamamlanma, ayrıca addım yoxdur), onlayn/ərazi (bax bölmə 6.2, 6.3, 7.2)
$router->get('/sse/lovhe', [SseController::class, 'lovhe'], [Auth::class, KuryeGuard::class]);
$router->get('/kurye/sifarislerim', [SifarisController::class, 'kuryeSifarisleri'], [Auth::class, KuryeGuard::class]);
$router->post('/sifaris/{id}/gotur', [SifarisController::class, 'gotur'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->post('/kurye/onlayn', [KuryeController::class, 'onlayn'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->get('/kurye/bolgeler', [KuryeController::class, 'bolgelerimGoster'], [Auth::class, KuryeGuard::class]);
$router->post('/kurye/bolgeler', [KuryeController::class, 'bolgeler'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);
$router->get('/kurye/abunelik', [KuryeController::class, 'abuneligim'], [Auth::class, KuryeGuard::class]);
$router->get('/kurye/profilim', [KuryeController::class, 'profilim'], [Auth::class, KuryeGuard::class]);
$router->post('/kurye/sekil', [KuryeController::class, 'sekilYukle'], [Auth::class, KuryeGuard::class, CsrfGuard::class]);

// Ərazi axtarışı — ictimai (bax bölmə 5.2)
$router->get('/sehirler', [EraziController::class, 'sehirler']);
$router->get('/sehir/{sehirId}/rayonlar', [EraziController::class, 'rayonlar']);

// Banner şəkli göstərmə (bax bölmə 8.4, admin panelində yüklənir, burada yayımlanır)
$router->get('/banner-sekil/{fayl}', [BannerImageController::class, 'goster']);
$router->get('/kurye-sekil/{fayl}', [KuryeSekilController::class, 'goster']);

// Abunə ödənişi (bax bölmə 9.1)
$router->post('/odenis/basla', [OdenisController::class, 'basla'], [Auth::class, KuryeGuard::class, CsrfGuard::class, RateLimit::class]);
$router->post('/webhook/odenis', [OdenisController::class, 'webhook'], [RateLimit::class]);

// Web Push abunəliyi (bax bölmə 9.3)
$router->post('/push/abune', [PushController::class, 'abune'], [Auth::class, CsrfGuard::class]);
$router->post('/push/legv', [PushController::class, 'legv'], [Auth::class, CsrfGuard::class]);

// HTML səhifələr (bax bölmə 9.2, Views qatı) — Faza 6
$router->get('/', [AppPageController::class, 'landing']);
$router->get('/giris', [AppPageController::class, 'giris']);
$router->get('/qeydiyyat', [AppPageController::class, 'qeydiyyat']);
$router->get('/panel', [AppPageController::class, 'panel']);
$router->get('/lovhe', [AppPageController::class, 'lovhe']);
$router->get('/profil', [AppPageController::class, 'profil']);
$router->get('/sifarislerim', [AppPageController::class, 'sifarislerim']);

// Hüquqi sənədlər (bax bölmə 1 "Hüquqi qeyd", Faza 7) — ictimai, giriş tələb olunmur
$router->get('/huquqi', [LegalController::class, 'index']);
$router->get('/huquqi/{slug}', [LegalController::class, 'goster']);

return $router;
