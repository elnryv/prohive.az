<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
$router->setNotFound(static function (): void {
    View::render('admin/placeholder', ['title' => '404 — Admin']);
});

$router->get('/', static function (): void {
    View::render('admin/placeholder', ['title' => 'Admin — Birlikdə Getdik']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
