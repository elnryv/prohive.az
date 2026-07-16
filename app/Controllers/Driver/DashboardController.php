<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

final class DashboardController
{
    public function feed(): void
    {
        Auth::requireRole('driver', '/giris');
        $user = Auth::user();

        $scope = ($_GET['tab'] ?? 'baku') === 'intercity' ? 'intercity' : 'baku';
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $fromLocationId = (int) ($_GET['from_location_id'] ?? 0);
        $toLocationId = (int) ($_GET['to_location_id'] ?? 0);

        $where = ["l.status = 'active'", 'l.scope = ?'];
        $args = [$scope];
        if ($categoryId > 0) {
            $where[] = 'l.category_id = ?';
            $args[] = $categoryId;
        }
        if ($fromLocationId > 0) {
            $where[] = 'l.from_location_id = ?';
            $args[] = $fromLocationId;
        }
        if ($toLocationId > 0) {
            $where[] = 'l.to_location_id = ?';
            $args[] = $toLocationId;
        }

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY l.is_urgent DESC, l.created_at DESC LIMIT 50';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($args);

        $categories = DB::conn()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        View::render('driver/feed', [
            'pageTitle' => t('nav.feed'),
            'driverStatus' => $user['driver_status'],
            'rejectReason' => $user['reject_reason'],
            'isActive' => Auth::isActiveDriver($user),
            'listings' => $stmt->fetchAll(),
            'scope' => $scope,
            'categories' => $categories,
            'locations' => $locations,
            'filters' => compact('categoryId', 'fromLocationId', 'toLocationId'),
        ]);
    }
}
