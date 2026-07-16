<?php
declare(strict_types=1);

define('APP_CONTEXT', 'admin');
require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
$router->setNotFound(static function (): void {
    http_response_code(404);
    View::render('admin/placeholder', ['title' => '404 — Admin']);
});

// ===================== Giriş =====================
$router->get('/giris', static function (): void {
    (new AdminLogin())->form();
});
$router->post('/giris', static function (): void {
    (new AdminLogin())->submit();
});
$router->get('/cixis', static function (): void {
    (new AdminLogin())->logout();
});

// ===================== Dashboard =====================
$router->get('/', static function (): void {
    (new AdminDashboard())->index();
});

// ===================== Təsdiq növbəsi =====================
$router->get('/tesdiq', static function (): void {
    (new Approvals())->index();
});
$router->get('/tesdiq/{id}', static function (array $p): void {
    (new Approvals())->show($p);
});
$router->post('/tesdiq/{id}/tesdiqle', static function (array $p): void {
    (new Approvals())->approve($p);
});
$router->post('/tesdiq/{id}/geri-gonder', static function (array $p): void {
    (new Approvals())->reject($p);
});
$router->post('/tesdiq/foto/{photoId}/tesdiqle', static function (array $p): void {
    (new Approvals())->approvePhoto($p);
});
$router->post('/tesdiq/foto/{photoId}/redd', static function (array $p): void {
    (new Approvals())->declinePhoto($p);
});

// ===================== Ev sahibləri =====================
$router->get('/owners', static function (): void {
    (new Owners())->index();
});
$router->get('/owners/{id}', static function (array $p): void {
    (new OwnerCard())->show($p);
});
$router->post('/owners/{id}/pulsuz', static function (array $p): void {
    (new OwnerCard())->setFree($p);
});
$router->post('/owners/{id}/pullu', static function (array $p): void {
    (new OwnerCard())->setPaid($p);
});
$router->post('/owners/{id}/qiymeti-sil', static function (array $p): void {
    (new OwnerCard())->clearPrice($p);
});
$router->post('/owners/{id}/blokla', static function (array $p): void {
    (new OwnerCard())->block($p);
});
$router->post('/owners/{id}/aktivlesdir', static function (array $p): void {
    (new OwnerCard())->activate($p);
});

// ===================== Parametrlər =====================
$router->get('/settings', static function (): void {
    (new Settings())->index();
});
$router->post('/settings', static function (): void {
    (new Settings())->update();
});

// ===================== Bölgələr =====================
$router->get('/regions', static function (): void {
    (new Regions())->index();
});
$router->post('/regions', static function (): void {
    (new Regions())->create();
});
$router->post('/regions/{id}', static function (array $p): void {
    (new Regions())->update($p);
});
$router->post('/regions/{id}/sil', static function (array $p): void {
    (new Regions())->delete($p);
});

// ===================== Şəraitlər =====================
$router->get('/amenities', static function (): void {
    (new Amenities())->index();
});
$router->post('/amenities', static function (): void {
    (new Amenities())->create();
});
$router->post('/amenities/{id}', static function (array $p): void {
    (new Amenities())->update($p);
});
$router->post('/amenities/{id}/sil', static function (array $p): void {
    (new Amenities())->delete($p);
});

// ===================== Ödənişlər =====================
$router->get('/payments', static function (): void {
    (new Payments())->index();
});

// ===================== Loglar =====================
$router->get('/logs', static function (): void {
    (new Logs())->index();
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
