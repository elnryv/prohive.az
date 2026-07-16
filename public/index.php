<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Router;
use App\Core\View;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\PublicListingController;
use App\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Controllers\Customer\ListingController as CustomerListing;
use App\Controllers\Customer\HistoryController as CustomerHistory;
use App\Controllers\Driver\DashboardController as DriverDashboard;
use App\Controllers\Driver\ListingController as DriverListing;

Auth::boot();

$router = new Router();

$router->get('/', function () {
    (new HomeController())->index();
});

// --- Auth (FAZA 1) ---
$router->get('/qeydiyyat', function () {
    (new AuthController())->registerForm();
});
$router->post('/qeydiyyat', function () {
    (new AuthController())->register();
});
$router->get('/giris', function () {
    (new AuthController())->loginForm();
});
$router->post('/giris', function () {
    (new AuthController())->login();
});
$router->post('/cixis', function () {
    (new AuthController())->logout();
});

// --- Müştəri (FAZA 2) ---
$router->get('/musteri/elanlarim', function () {
    (new CustomerDashboard())->index();
});
$router->get('/musteri/elan/yeni', function () {
    (new CustomerListing())->createForm();
});
$router->post('/musteri/elan/yeni', function () {
    (new CustomerListing())->create();
});
$router->get('/musteri/elan/{id}', function ($p) {
    (new CustomerListing())->show($p);
});
$router->post('/musteri/elan/{id}/sil', function ($p) {
    (new CustomerListing())->remove($p);
});
$router->post('/musteri/elan/{id}/uzat', function ($p) {
    (new CustomerListing())->extend($p);
});
$router->get('/musteri/tarixce', function () {
    (new CustomerHistory())->index();
});
$router->get('/musteri/tarixce/{id}/yenidenSifaris', function ($p) {
    (new CustomerHistory())->reorderForm($p);
});
$router->get('/musteri/profil', function () {
    Auth::requireRole('customer', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});

// --- Sürücü (FAZA 2, təklif FAZA 3-də) ---
$router->get('/surucu/lent', function () {
    (new DriverDashboard())->feed();
});
$router->get('/surucu/elan/{id}', function ($p) {
    (new DriverListing())->show($p);
});
$router->get('/surucu/profil', function () {
    Auth::requireRole('driver', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});

// --- Public paylaşım kartı (Q-Y10) ---
$router->get('/e/{code}', function ($p) {
    (new PublicListingController())->show($p);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
