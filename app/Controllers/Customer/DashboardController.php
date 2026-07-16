<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('customer', '/giris');
        // Tam siyahı/status/filtr FAZA 2-də (Elan idarəsi, bölmə 6.3).
        View::render('customer/listings_stub', ['pageTitle' => t('nav.my_listings')]);
    }
}
