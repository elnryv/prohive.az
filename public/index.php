<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->get('/', static function (): void {
    (new Home())->index();
});

$router->get('/bolge/{slug}', static function (array $params): void {
    (new Region())->show($params);
});

$router->get('/ev/{slug}', static function (array $params): void {
    (new House())->show($params);
});

$router->get('/axtar', static function (): void {
    (new Search())->index();
});

$router->post('/api/track/wa', static function (): void {
    (new Track())->wa();
});

$router->get('/haqqinda', static function (): void {
    (new StaticPage())->about();
});

$router->get('/sertler', static function (): void {
    (new StaticPage())->terms();
});

$router->get('/mexfilik', static function (): void {
    (new StaticPage())->privacy();
});

$router->get('/ev-sahibi-ol', static function (): void {
    (new StaticPage())->becomeHost();
});

// ===================== Ev sahibi (bölmə 7) =====================

$router->get('/sahib/qeydiyyat', static function (): void {
    (new Register())->form();
});
$router->post('/sahib/qeydiyyat', static function (): void {
    (new Register())->submit();
});

$router->get('/sahib/giris', static function (): void {
    (new Login())->form();
});
$router->post('/sahib/giris', static function (): void {
    (new Login())->submit();
});
$router->get('/sahib/cixis', static function (): void {
    (new Login())->logout();
});

$router->get('/sahib/panel', static function (): void {
    (new Dashboard())->index();
});

$router->get('/sahib/abune', static function (): void {
    (new Billing())->index();
});
$router->post('/sahib/odenis/basla', static function (): void {
    (new Billing())->pay();
});

$router->post('/odenis/callback', static function (): void {
    (new PaymentCallback())->handle();
});

$router->get('/sahib/ev/yeni', static function (): void {
    (new HouseEdit())->create();
});
$router->post('/sahib/ev/yeni', static function (): void {
    (new HouseEdit())->createSubmit();
});
$router->get('/sahib/ev/{id}/redakte', static function (array $params): void {
    (new HouseEdit())->edit($params);
});
$router->post('/sahib/ev/{id}/redakte', static function (array $params): void {
    (new HouseEdit())->update($params);
});
$router->post('/sahib/ev/{id}/gonder', static function (array $params): void {
    (new HouseEdit())->submitForApproval($params);
});

$router->post('/sahib/ev/{id}/fotolar', static function (array $params): void {
    (new Photos())->upload($params);
});
$router->post('/sahib/ev/{id}/fotolar/{photoId}/sil', static function (array $params): void {
    (new Photos())->delete($params);
});
$router->post('/sahib/ev/{id}/fotolar/{photoId}/cover', static function (array $params): void {
    (new Photos())->setCover($params);
});

$router->get('/sahib/teqvim', static function (): void {
    (new Calendar())->index();
});
$router->post('/sahib/teqvim/{id}/gun', static function (array $params): void {
    (new Calendar())->toggleDay($params);
});
$router->post('/sahib/teqvim/{id}/aralig', static function (array $params): void {
    (new Calendar())->toggleRange($params);
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
