<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Router;
use App\Core\View;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\AuthController;
use App\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Controllers\Driver\DashboardController as DriverDashboard;

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

// --- Müştəri (stub, FAZA 2-də tam) ---
$router->get('/musteri/elanlarim', function () {
    (new CustomerDashboard())->index();
});
$router->get('/musteri/profil', function () {
    Auth::requireRole('customer', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});

// --- Sürücü (stub, FAZA 2/3-də tam) ---
$router->get('/surucu/lent', function () {
    (new DriverDashboard())->feed();
});
$router->get('/surucu/profil', function () {
    Auth::requireRole('driver', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
