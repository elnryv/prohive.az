<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

final class MyOffersController
{
    public function index(): void
    {
        Auth::requireRole('driver', '/giris');
        $driverId = (int) Auth::id();

        $tab = (string) ($_GET['tab'] ?? 'pending');
        $statusMap = [
            'pending' => ['pending'],
            'accepted' => ['accepted'],
            'lost' => ['lost'],
            'withdrawn' => ['withdrawn', 'canceled_by_customer'],
        ];
        $statuses = $statusMap[$tab] ?? $statusMap['pending'];

        // Q-Y3: müştəri nömrəsi (u.phone) YALNIZ bu sorğuda, YALNIZ giriş etmiş sürücünün
        // ÖZ təklifləri üçün seçilir; view isə onu yalnız status==='accepted' olanda göstərir.
        // Diqqət: o.id/l.id kimi eyni adlı sütunların FETCH_ASSOC-da bir-birini əvəz
        // etməməsi üçün offer sütunları açıq şəkildə ləqəblənib.
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT o.id as offer_id, o.price, o.note, o.status as offer_status,
                       o.created_at as offer_created_at, o.updated_at as offer_updated_at,
                       " . ListingRules::SELECT_SQL . ", u.full_name as customer_name, u.phone as customer_phone
                FROM offers o
                JOIN listings l ON l.id = o.listing_id
                JOIN categories c ON c.id = l.category_id
                JOIN locations fl ON fl.id = l.from_location_id
                JOIN locations tl ON tl.id = l.to_location_id
                JOIN users u ON u.id = l.customer_id
                WHERE o.driver_id = ? AND o.status IN ({$placeholders})
                  AND l.status <> 'completed'
                ORDER BY o.updated_at DESC LIMIT 100";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$driverId, ...$statuses]);

        View::render('driver/my_offers', [
            'pageTitle' => t('nav.my_offers'),
            'offers' => $stmt->fetchAll(),
            'tab' => $tab,
        ]);
    }
}
