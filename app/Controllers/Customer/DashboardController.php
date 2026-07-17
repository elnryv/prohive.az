<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Banners;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('customer', '/giris');

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . "
             WHERE l.customer_id = ? AND l.status = 'active' ORDER BY l.created_at DESC LIMIT 50";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([Auth::id()]);

        View::render('customer/listings_index', [
            'pageTitle' => t('nav.my_listings'),
            'listings' => $stmt->fetchAll(),
            'justCreated' => isset($_GET['xosgeldin']),
            'banners' => Banners::active(),
        ]);
    }
}
