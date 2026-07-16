<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\AdminAuth;

AdminAuth::boot();

$router = new Router();

$router->get('/', function () {
    header('Location: ' . (AdminAuth::check() ? '/dashboard' : '/giris'));
});

// FAZA 6-da tam admin marşrutları (dashboard, sürücülər, müştərilər, elanlar,
// ödənişlər, parametrlər, push kampaniya, loglar) buraya əlavə olunacaq.

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
