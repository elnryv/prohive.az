<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;

/** Müştərinin sürücü/elan haqqında şikayəti (bax Admin\ListingController::resolveReport). */
final class ReportController
{
    public function submit(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $customerId = (int) Auth::id();
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '') {
            header('Location: /musteri/elan/' . $listingId);
            return;
        }
        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        $stmt = DB::conn()->prepare(
            'SELECT accepted_offer_id FROM listings WHERE id = ? AND customer_id = ? LIMIT 1'
        );
        $stmt->execute([$listingId, $customerId]);
        $listing = $stmt->fetch();
        if ($listing === false) {
            header('Location: /musteri/elanlarim');
            return;
        }

        $dupStmt = DB::conn()->prepare(
            "SELECT id FROM reports WHERE listing_id = ? AND reporter_user_id = ? AND status = 'pending' LIMIT 1"
        );
        $dupStmt->execute([$listingId, $customerId]);
        if ($dupStmt->fetch() !== false) {
            header('Location: /musteri/elan/' . $listingId . '?sikayet=artiq_var');
            return;
        }

        $ins = DB::conn()->prepare(
            'INSERT INTO reports (listing_id, offer_id, reporter_user_id, reason) VALUES (?, ?, ?, ?)'
        );
        $ins->execute([$listingId, $listing['accepted_offer_id'], $customerId, $reason]);

        header('Location: /musteri/elan/' . $listingId . '?sikayet=ok');
    }
}
