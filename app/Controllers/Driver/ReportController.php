<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;

/** Sürücünün müştəri/elan haqqında şikayəti (bax Admin\ListingController::resolveReport). */
final class ReportController
{
    public function submit(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $driverId = (int) Auth::id();
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '') {
            header('Location: /surucu/tekliflerim?tab=accepted');
            return;
        }
        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        $stmt = DB::conn()->prepare(
            'SELECT id FROM offers WHERE listing_id = ? AND driver_id = ? LIMIT 1'
        );
        $stmt->execute([$listingId, $driverId]);
        $offer = $stmt->fetch();
        if ($offer === false) {
            header('Location: /surucu/tekliflerim?tab=accepted');
            return;
        }

        $dupStmt = DB::conn()->prepare(
            "SELECT id FROM reports WHERE listing_id = ? AND reporter_user_id = ? AND status = 'pending' LIMIT 1"
        );
        $dupStmt->execute([$listingId, $driverId]);
        if ($dupStmt->fetch() !== false) {
            header('Location: /surucu/tekliflerim?tab=accepted');
            return;
        }

        $ins = DB::conn()->prepare(
            'INSERT INTO reports (listing_id, offer_id, reporter_user_id, reason) VALUES (?, ?, ?, ?)'
        );
        $ins->execute([$listingId, $offer['id'], $driverId, $reason]);

        header('Location: /surucu/tekliflerim?tab=accepted&sikayet=ok');
    }
}
