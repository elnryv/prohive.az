<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();

$router->get('/', static function (): void {
    (new Home())->index();
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
