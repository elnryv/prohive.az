<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

/** İş tarixçəsi (Q-Y11, bölmə 7.5): tamamlanmış işlər + aylıq qazanc cəmi. */
final class HistoryController
{
    public function index(): void
    {
        Auth::requireRole('driver', '/giris');
        $driverId = (int) Auth::id();

        $sql = "SELECT o.price, " . ListingRules::SELECT_SQL . "
                FROM offers o
                JOIN listings l ON l.id = o.listing_id
                JOIN categories c ON c.id = l.category_id
                JOIN locations fl ON fl.id = l.from_location_id
                JOIN locations tl ON tl.id = l.to_location_id
                WHERE o.driver_id = ? AND o.status = 'accepted' AND l.status = 'completed'
                ORDER BY l.completed_at DESC LIMIT 100";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$driverId]);
        $jobs = $stmt->fetchAll();

        $monthStmt = DB::conn()->prepare(
            "SELECT COUNT(*) cnt, COALESCE(SUM(o.price),0) total
             FROM offers o JOIN listings l ON l.id = o.listing_id
             WHERE o.driver_id = ? AND o.status = 'accepted' AND l.status = 'completed'
               AND l.completed_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        $monthStmt->execute([$driverId]);
        $monthly = $monthStmt->fetch();

        $cancelStmt = DB::conn()->prepare('SELECT cancel_count FROM users WHERE id = ?');
        $cancelStmt->execute([$driverId]);

        View::render('driver/history', [
            'pageTitle' => t('nav.history'),
            'jobs' => $jobs,
            'monthlyCount' => (int) $monthly['cnt'],
            'monthlyTotal' => (float) $monthly['total'],
            'cancelCount' => (int) $cancelStmt->fetch()['cancel_count'],
        ]);
    }
}
