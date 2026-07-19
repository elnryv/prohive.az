<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Router;
use App\Core\View;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\PublicListingController;
use App\Controllers\Site\LegalController;
use App\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Controllers\Customer\ListingController as CustomerListing;
use App\Controllers\Customer\HistoryController as CustomerHistory;
use App\Controllers\Customer\OfferActionController as CustomerOfferAction;
use App\Controllers\Customer\RatingController as CustomerRating;
use App\Controllers\Customer\ReportController as CustomerReport;
use App\Controllers\Driver\DashboardController as DriverDashboard;
use App\Controllers\Driver\ListingController as DriverListing;
use App\Controllers\Driver\OfferController as DriverOffer;
use App\Controllers\Driver\MyOffersController as DriverMyOffers;
use App\Controllers\Driver\HistoryController as DriverHistory;
use App\Controllers\Driver\RouteSubscriptionController as DriverRoutes;
use App\Controllers\Driver\RatingController as DriverRating;
use App\Controllers\Driver\ReportController as DriverReport;
use App\Controllers\Site\StreamController;
use App\Controllers\Site\PushController;
use App\Controllers\Site\ProfileController;
use App\Controllers\Driver\BillingController as DriverBilling;
use App\Controllers\Site\PaymentCallbackController;

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
$router->post('/musteri/elan/{id}/teklif/{offerId}/qebul', function ($p) {
    (new CustomerOfferAction())->accept($p);
});
$router->post('/musteri/elan/{id}/legv', function ($p) {
    (new CustomerOfferAction())->cancel($p);
});
$router->post('/musteri/elan/{id}/reytinq', function ($p) {
    (new CustomerRating())->submit($p);
});
$router->post('/musteri/elan/{id}/sikayet', function ($p) {
    (new CustomerReport())->submit($p);
});
$router->get('/musteri/profil', function () {
    Auth::requireRole('customer', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});
$router->post('/musteri/profil', function () {
    Auth::requireRole('customer', '/giris');
    (new ProfileController())->update();
});

// --- Sürücü (FAZA 2, təklif FAZA 3-də) ---
$router->get('/surucu/lent', function () {
    (new DriverDashboard())->feed();
});
$router->get('/surucu/elan/{id}', function ($p) {
    (new DriverListing())->show($p);
});
$router->post('/surucu/elan/{id}/teklif', function ($p) {
    (new DriverOffer())->submit($p);
});
$router->post('/surucu/elan/{id}/teklif/geri', function ($p) {
    (new DriverOffer())->withdraw($p);
});
$router->post('/surucu/elan/{id}/legv', function ($p) {
    (new DriverOffer())->cancelAccepted($p);
});
$router->post('/surucu/elan/{id}/sikayet', function ($p) {
    (new DriverReport())->submit($p);
});
$router->get('/surucu/tekliflerim', function () {
    (new DriverMyOffers())->index();
});
$router->get('/surucu/tarixce', function () {
    (new DriverHistory())->index();
});
$router->post('/surucu/tarixce/{id}/reytinq', function ($p) {
    (new DriverRating())->submit($p);
});
$router->get('/surucu/marsrutlar', function () {
    (new DriverRoutes())->index();
});
$router->post('/surucu/marsrutlar', function () {
    (new DriverRoutes())->add();
});
$router->post('/surucu/marsrutlar/{id}/sil', function ($p) {
    (new DriverRoutes())->remove($p);
});
$router->get('/surucu/profil', function () {
    Auth::requireRole('driver', '/giris');
    View::render('site/profile_stub', ['pageTitle' => t('nav.profile')]);
});
$router->post('/surucu/profil', function () {
    Auth::requireRole('driver', '/giris');
    (new ProfileController())->update();
});
$router->get('/surucu/odenis', function () {
    (new DriverBilling())->show();
});
$router->post('/surucu/odenis/ode', function () {
    (new DriverBilling())->pay();
});

// --- Hüquqi sənədlər ---
$router->get('/huquqi', function () {
    (new LegalController())->index();
});
$router->get('/huquqi/{slug}', function ($p) {
    (new LegalController())->show($p);
});

// --- Public paylaşım kartı (Q-Y10) ---
$router->get('/e/{code}', function ($p) {
    (new PublicListingController())->show($p);
});

// --- SSE axını (FAZA 4, bölmə 11.5) ---
$router->get('/axin/lent', function () {
    (new StreamController())->feed();
});
$router->get('/axin/musteri', function () {
    (new StreamController())->customer();
});

// --- Web Push (FAZA 4, bölmə 11.4) ---
$router->get('/push/vapid-acar', function () {
    (new PushController())->vapidPublicKey();
});
$router->post('/push/abune', function () {
    (new PushController())->subscribe();
});
$router->post('/push/legv', function () {
    (new PushController())->unsubscribe();
});

// --- Payriff callback (bölmə 8) ---
$router->post('/odenis/callback', function () {
    (new PaymentCallbackController())->handle();
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
