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

        $categories = DB::conn()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        // Səhifə render olunan andakı son sse_events.id: EventSource bu ID-dən BAŞLAYARAQ
        // abunə olunur ki, köhnə (artıq səhifədə göstərilmiş) hadisələr "yeni" kimi təkrar
        // oynadılmasın (məs. hər dəfə lent yenilənəndə bütün tarixçənin animasiya ilə düşməsi).
        $lastEventId = (int) (DB::conn()->query('SELECT MAX(id) m FROM sse_events')->fetch()['m'] ?? 0);

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
            'lastEventId' => $lastEventId,
            'banners' => Banners::active(),
        ]);
    }

    /**
     * SSE `listing_new` hadisəsindən sonra JS tərəfindən çağırılır — tək elanın kart HTML-i
     * qaytarılır (yalnız hələ aktivdirsə). Kart render məntiqi PHP-də tək yerdə qalır.
     */
    public function cardFragment(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        $id = (int) $params['id'];

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . "
             WHERE l.id = ? AND l.status = 'active' LIMIT 1";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$id]);
        $listing = $stmt->fetch();

        header('Content-Type: application/json');
        if ($listing === false) {
            echo json_encode(['ok' => false]);
            return;
        }

        echo json_encode([
            'ok' => true,
            'scope' => $listing['scope'],
            'html' => View::capture('partials/listing_card', ['listing' => $listing, 'href' => '/surucu/elan/' . $id]),
        ]);
    }
}
