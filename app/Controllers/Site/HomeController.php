<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('site/home', ['pageTitle' => null, 'noindex' => false]);
    }
}
