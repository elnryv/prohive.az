<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Banners;
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
        $listings = $stmt->fetchAll();

        // Q-Y12 genişlənməsi: marşrut abunəliyinə uyğun elanlar lentin başında göstərilir
        // (bax RouteSubscriptionController — bildiriş davranışına TOXUNULMUR, yalnız sıralama/vurğu).
        $subsStmt = DB::conn()->prepare('SELECT from_location_id, to_location_id, scope FROM route_subscriptions WHERE driver_id = ?');
        $subsStmt->execute([(int) Auth::id()]);
        $subscriptions = $subsStmt->fetchAll();

        if ($subscriptions !== []) {
            foreach ($listings as &$l) {
                $l['route_matched'] = ListingRules::matchesAnySubscription($l, $subscriptions);
            }
            unset($l);
            usort($listings, static fn (array $a, array $b): int => ($b['route_matched'] <=> $a['route_matched']));
        }

        $categories = DB::conn()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        $todayStmt = DB::conn()->prepare(
            "SELECT COUNT(*) c FROM listings WHERE status = 'active' AND scope = ? AND created_at >= CURDATE()"
        );
        $todayStmt->execute([$scope]);
        $todayCount = (int) $todayStmt->fetch()['c'];

        // Səhifə render olunan andakı son sse_events.id: EventSource bu ID-dən BAŞLAYARAQ
        // abunə olunur ki, köhnə (artıq səhifədə göstərilmiş) hadisələr "yeni" kimi təkrar
        // oynadılmasın (məs. hər dəfə lent yenilənəndə bütün tarixçənin animasiya ilə düşməsi).
        $lastEventId = (int) (DB::conn()->query('SELECT MAX(id) m FROM sse_events')->fetch()['m'] ?? 0);

        View::render('driver/feed', [
            'pageTitle' => t('nav.feed'),
            'driverStatus' => $user['driver_status'],
            'rejectReason' => $user['reject_reason'],
            'isActive' => Auth::isActiveDriver($user),
            'listings' => $listings,
            'scope' => $scope,
            'categories' => $categories,
            'locations' => $locations,
            'filters' => compact('categoryId', 'fromLocationId', 'toLocationId'),
            'lastEventId' => $lastEventId,
            'banners' => Banners::active(),
            'todayCount' => $todayCount,
            'activeCount' => count($listings),
            'routeSubscriptions' => $subscriptions,
        ]);
    }
}
