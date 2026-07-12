<?php

declare(strict_types=1);

use App\Controllers\AdminAbunelikController;
use App\Controllers\AdminAuthController;
use App\Controllers\AdminBannerController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminEraziController;
use App\Controllers\AdminPageController;
use App\Controllers\AdminSifarisController;
use App\Controllers\AdminUserController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AdminAuth;
use App\Middleware\CsrfGuard;
use App\Middleware\RateLimit;

/**
 * appadmin.birlikde.biz marşrutları (izolyasiya olunmuş admin paneli) — bax
 * CLAUDE.md bölmə 8.
 */

$router = new Router();

$router->get('/healthz', function (Request $request) {
    Response::json(['status' => 'ok']);
});

$router->get('/csrf-token', function (Request $request) {
    Response::json(['csrf_token' => \App\Core\Csrf::token()]);
});

$router->post('/giris', [AdminAuthController::class, 'giris'], [CsrfGuard::class, RateLimit::class]);
$router->post('/cixis', [AdminAuthController::class, 'cixis'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/parol-deyis', [AdminAuthController::class, 'parolDeyis'], [AdminAuth::class, CsrfGuard::class]);

// Müştəri/kuryer siyahıları, pop-up detal, bloklama (bax bölmə 8.2)
$router->get('/musteriler', [AdminUserController::class, 'musteriler'], [AdminAuth::class]);
$router->get('/kuryerler', [AdminUserController::class, 'kuryerler'], [AdminAuth::class]);
$router->get('/istifadeci/{id}', [AdminUserController::class, 'detal'], [AdminAuth::class]);
$router->post('/istifadeci/{id}/blokla', [AdminUserController::class, 'blokla'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/istifadeci/{id}/blokdan-cixar', [AdminUserController::class, 'blokdanCixar'], [AdminAuth::class, CsrfGuard::class]);

// Sifariş idarəsi, filtr, pagination (bax bölmə 8.3)
$router->get('/sifarisler', [AdminSifarisController::class, 'siyahi'], [AdminAuth::class]);
$router->get('/sifaris/{id}', [AdminSifarisController::class, 'detal'], [AdminAuth::class]);

// Abunə idarəsi — fərdi + qlobal (bax bölmə 8.5)
$router->get('/kurye/{kuryeId}/abunelik', [AdminAbunelikController::class, 'status'], [AdminAuth::class]);
$router->post('/kurye/{kuryeId}/abunelik/uzat', [AdminAbunelikController::class, 'uzat'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/kurye/{kuryeId}/abunelik/tip', [AdminAbunelikController::class, 'tipDeyis'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/kurye/{kuryeId}/abunelik/aktivlik', [AdminAbunelikController::class, 'aktivlikDeyis'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/abune-rejimi', [AdminAbunelikController::class, 'qlobalRejim'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/kuryeler/abunelik/hamisi', [AdminAbunelikController::class, 'hamisiniDeyis'], [AdminAuth::class, CsrfGuard::class]);

// Banner sistemi (bax bölmə 8.4)
$router->post('/banner', [AdminBannerController::class, 'yarat'], [AdminAuth::class, CsrfGuard::class]);
$router->get('/bannerler', [AdminBannerController::class, 'siyahi'], [AdminAuth::class]);
$router->post('/banner/{id}/aktivlik', [AdminBannerController::class, 'aktivlikDeyis'], [AdminAuth::class, CsrfGuard::class]);

// Dashboard — canlı sayğaclar, son hadisələr (bax bölmə 8.1)
$router->get('/dashboard', [AdminDashboardController::class, 'goster'], [AdminAuth::class]);

// Ərazi idarəsi — şəhər/rayon əlavə/deaktiv/aktiv/sil (bax bölmə 8.7)
$router->get('/sehirler', [AdminEraziController::class, 'sehirler'], [AdminAuth::class]);
$router->post('/sehir', [AdminEraziController::class, 'sehirYarat'], [AdminAuth::class, CsrfGuard::class]);
$router->get('/sehir/{sehirId}/rayonlar', [AdminEraziController::class, 'rayonlar'], [AdminAuth::class]);
$router->post('/sehir/{sehirId}/rayon', [AdminEraziController::class, 'rayonYarat'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/rayon/{id}/aktivlik', [AdminEraziController::class, 'rayonAktivlik'], [AdminAuth::class, CsrfGuard::class]);
$router->post('/rayon/{id}/sil', [AdminEraziController::class, 'rayonSil'], [AdminAuth::class, CsrfGuard::class]);

// HTML səhifələr (Faza 6) — Views qatı, /panel/* altında (JSON API-lərlə toqquşmasın deyə)
$router->get('/', [AdminPageController::class, 'landing']);
$router->get('/giris', [AdminPageController::class, 'giris']);
$router->get('/panel', [AdminPageController::class, 'dashboard']);
$router->get('/panel/musteriler', [AdminPageController::class, 'musteriler']);
$router->get('/panel/kuryerler', [AdminPageController::class, 'kuryerler']);
$router->get('/panel/sifarisler', [AdminPageController::class, 'sifarisler']);
$router->get('/panel/bannerler', [AdminPageController::class, 'bannerler']);
$router->get('/panel/erazi', [AdminPageController::class, 'erazi']);

return $router;
