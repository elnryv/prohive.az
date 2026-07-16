<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $pdo = DB::conn();

        $todayListings = (int) $pdo->query("SELECT COUNT(*) c FROM listings WHERE created_at >= CURDATE()")->fetch()['c'];
        $activeListings = (int) $pdo->query("SELECT COUNT(*) c FROM listings WHERE status = 'active'")->fetch()['c'];
        $todayAccepted = (int) $pdo->query("SELECT COUNT(*) c FROM listings WHERE accepted_at >= CURDATE()")->fetch()['c'];
        $pendingDrivers = (int) $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'driver' AND driver_status = 'pending'")->fetch()['c'];

        $billingBreakdown = $pdo->query(
            "SELECT billing_status, COUNT(*) c FROM users WHERE role = 'driver' AND driver_status = 'approved' GROUP BY billing_status"
        )->fetchAll();

        $mrr = (float) ($pdo->query(
            "SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status = 'paid' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        )->fetch()['s'] ?? 0);

        $routeHeat = $pdo->query(
            "SELECT fl.name_az as from_name, tl.name_az as to_name,
                    COUNT(*) as listing_count,
                    SUM(l.offers_count) as offer_count,
                    SUM(l.status IN ('accepted','completed')) as accepted_count
             FROM listings l
             JOIN locations fl ON fl.id = l.from_location_id
             JOIN locations tl ON tl.id = l.to_location_id
             WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY l.from_location_id, l.to_location_id
             ORDER BY listing_count DESC
             LIMIT 10"
        )->fetchAll();

        View::render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'todayListings' => $todayListings,
            'activeListings' => $activeListings,
            'todayAccepted' => $todayAccepted,
            'pendingDrivers' => $pendingDrivers,
            'billingBreakdown' => $billingBreakdown,
            'mrr' => $mrr,
            'routeHeat' => $routeHeat,
        ], 'layouts/admin');
    }
}
