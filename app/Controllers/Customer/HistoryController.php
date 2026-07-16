<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

/** Sifariş tarixçəsi (Q-Y11, bölmə 6.6) — tamamlanmış/bitmiş/silinmiş elanlar + "Yenidən sifariş". */
final class HistoryController
{
    public function index(): void
    {
        Auth::requireRole('customer', '/giris');

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . "
             WHERE l.customer_id = ? AND l.status IN ('completed','expired','removed')
             ORDER BY l.created_at DESC LIMIT 100";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([Auth::id()]);

        View::render('customer/history', [
            'pageTitle' => t('nav.history'),
            'listings' => $stmt->fetchAll(),
        ]);
    }

    /** Köhnə elanın kopyası ilə doldurulmuş yaratma forması (tarix boş, Q-Y11). */
    public function reorderForm(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT * FROM listings WHERE id = ? AND customer_id = ? LIMIT 1');
        $stmt->execute([$id, Auth::id()]);
        $old = $stmt->fetch();
        if ($old === false) {
            http_response_code(404);
            View::render('site/404', []);
            return;
        }

        $categories = DB::conn()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        View::render('customer/listing_form', [
            'pageTitle' => t('nav.new_listing'),
            'categories' => $categories,
            'locations' => $locations,
            'errors' => [],
            'old' => [
                'category_id' => $old['category_id'],
                'from_location_id' => $old['from_location_id'],
                'from_detail' => $old['from_detail'],
                'to_location_id' => $old['to_location_id'],
                'to_detail' => $old['to_detail'],
                'description' => $old['description'],
            ],
        ]);
    }
}
