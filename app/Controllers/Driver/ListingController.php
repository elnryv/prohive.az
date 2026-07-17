<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\View;

final class ListingController
{
    public function show(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        $id = (int) $params['id'];

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . '
             WHERE l.id = ? LIMIT 1';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$id]);
        $listing = $stmt->fetch();

        if ($listing === false) {
            http_response_code(404);
            View::render('site/404', []);
            return;
        }

        $photos = DB::conn()->prepare('SELECT * FROM listing_photos WHERE listing_id = ? ORDER BY sort_order');
        $photos->execute([$id]);

        $customerStmt = DB::conn()->prepare('SELECT full_name, profile_photo FROM users WHERE id = ? LIMIT 1');
        $customerStmt->execute([(int) $listing['customer_id']]);
        $customer = $customerStmt->fetch() ?: ['full_name' => '', 'profile_photo' => null];

        $user = Auth::user();
        $myOfferStmt = DB::conn()->prepare('SELECT * FROM offers WHERE listing_id = ? AND driver_id = ? LIMIT 1');
        $myOfferStmt->execute([$id, Auth::id()]);

        View::render('driver/listing_show', [
            'pageTitle' => t('nav.feed'),
            'listing' => $listing,
            'photos' => $photos->fetchAll(),
            'customer' => $customer,
            'isActive' => Auth::isActiveDriver($user),
            'driverStatus' => $user['driver_status'],
            'myOffer' => $myOfferStmt->fetch() ?: null,
        ]);
    }
}
