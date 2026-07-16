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

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
