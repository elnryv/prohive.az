<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\AdminAuth;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DriverController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\ListingController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\DictionaryController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\CampaignController;
use App\Controllers\Admin\LogController;

AdminAuth::boot();

$router = new Router();

$router->get('/', function () {
    header('Location: ' . (AdminAuth::check() ? '/dashboard' : '/giris'));
});

$router->get('/giris', function () {
    (new AdminAuthController())->loginForm();
});
$router->post('/giris', function () {
    (new AdminAuthController())->login();
});
$router->post('/cixis', function () {
    (new AdminAuthController())->logout();
});

$router->get('/dashboard', function () {
    (new DashboardController())->index();
});

// --- Sürücülər ---
$router->get('/surucular', function () {
    (new DriverController())->index();
});
$router->get('/surucular/{id}', function ($p) {
    (new DriverController())->show($p);
});
$router->post('/surucular/{id}/tesdiqle', function ($p) {
    (new DriverController())->approve($p);
});
$router->post('/surucular/{id}/redd', function ($p) {
    (new DriverController())->reject($p);
});
$router->post('/surucular/{id}/odenis-tipi', function ($p) {
    (new DriverController())->toggleFree($p);
});
$router->post('/surucular/{id}/qiymet', function ($p) {
    (new DriverController())->setCustomPrice($p);
});
$router->post('/surucular/{id}/blokla', function ($p) {
    (new DriverController())->block($p);
});

// --- Müştərilər ---
$router->get('/musteriler', function () {
    (new CustomerController())->index();
});
$router->get('/musteriler/{id}', function ($p) {
    (new CustomerController())->show($p);
});
$router->post('/musteriler/{id}/blokla', function ($p) {
    (new CustomerController())->block($p);
});

// --- Elanlar ---
$router->get('/elanlar', function () {
    (new ListingController())->index();
});
$router->get('/elanlar/{id}', function ($p) {
    (new ListingController())->show($p);
});
$router->post('/elanlar/{id}/sil', function ($p) {
    (new ListingController())->remove($p);
});
$router->post('/elanlar/sikayet/{id}', function ($p) {
    (new ListingController())->resolveReport($p);
});

// --- Parametrlər ---
$router->get('/parametrler', function () {
    (new SettingsController())->index();
});
$router->post('/parametrler/odenis-toggle', function () {
    (new SettingsController())->togglePayments();
});
$router->post('/parametrler/umumi', function () {
    (new SettingsController())->updateGeneral();
});
$router->post('/parametrler/texniki-fasile', function () {
    (new SettingsController())->toggleMaintenance();
});
$router->post('/parametrler/lugetler/{table}', function ($p) {
    (new DictionaryController())->add($p);
});
$router->post('/parametrler/lugetler/{table}/{id}/toggle', function ($p) {
    (new DictionaryController())->toggle($p);
});

// --- Ödənişlər ---
$router->get('/odenisler', function () {
    (new PaymentController())->index();
});

// --- Push kampaniya ---
$router->get('/kampaniya', function () {
    (new CampaignController())->index();
});
$router->post('/kampaniya/gonder', function () {
    (new CampaignController())->send();
});

// --- Loglar ---
$router->get('/loglar', function () {
    (new LogController())->index();
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
