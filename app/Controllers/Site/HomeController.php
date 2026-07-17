<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            header('Location: ' . (Auth::isDriver() ? '/surucu/lent' : '/musteri/elanlarim'));
            return;
        }
        View::render('site/home', ['pageTitle' => null, 'noindex' => false]);
    }
}
