<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

/**
 * Public paylaşım kartı (Q-Y10, bölmə 6.8). Loginsiz açılır. Server tərəfdə
 * SADƏCƏ təklif SAYI seçilir — qiymət/nömrə sütunları bu sorğuya belə daxil
 * edilmir (Q-Y3: "public səhifədə... nömrə OLMAZ").
 */
final class PublicListingController
{
    public function show(array $params): void
    {
        $code = (string) $params['code'];

        $sql = 'SELECT l.id, l.public_code, l.description, l.move_date, l.is_urgent, l.status, l.created_at,
                        ' . ListingRules::SELECT_SQL . '
                 ' . ListingRules::FROM_SQL . '
                 WHERE l.public_code = ? LIMIT 1';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$code]);
        $listing = $stmt->fetch();

        if ($listing === false) {
            http_response_code(404);
            View::render('site/404', []);
            return;
        }

        $offersCount = DB::conn()->prepare("SELECT COUNT(*) c FROM offers WHERE listing_id = ? AND status IN ('pending','accepted')");
        $offersCount->execute([$listing['id']]);

        $photos = DB::conn()->prepare('SELECT filename FROM listing_photos WHERE listing_id = ? ORDER BY sort_order');
        $photos->execute([$listing['id']]);

        $viewIncr = DB::conn()->prepare('UPDATE listings SET views_count = views_count + 1 WHERE id = ?');
        $viewIncr->execute([$listing['id']]);

        View::render('site/public_listing', [
            'pageTitle' => \App\Core\Lang::field($listing, 'from') . ' → ' . \App\Core\Lang::field($listing, 'to'),
            'listing' => $listing,
            'offersCount' => (int) $offersCount->fetch()['c'],
            'photos' => $photos->fetchAll(),
            'isLoggedIn' => Auth::check(),
        ], 'layouts/public');
    }
}
