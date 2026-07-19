<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;

/** Tamamlanmış işdən sonra müştərinin sürücüyə verdiyi reytinq. */
final class RatingController
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
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            header('Location: /musteri/elan/' . $listingId);
            return;
        }

        $stmt = DB::conn()->prepare(
            "SELECT o.driver_id FROM listings l
             JOIN offers o ON o.id = l.accepted_offer_id
             WHERE l.id = ? AND l.customer_id = ? AND l.status = 'completed' LIMIT 1"
        );
        $stmt->execute([$listingId, $customerId]);
        $row = $stmt->fetch();

        if ($row === false) {
            header('Location: /musteri/elan/' . $listingId);
            return;
        }

        $driverId = (int) $row['driver_id'];

        $pdo = DB::conn();
        $ins = $pdo->prepare(
            'INSERT IGNORE INTO ratings (listing_id, rater_user_id, rated_user_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$listingId, $customerId, $driverId, $rating, $comment !== '' ? $comment : null]);

        if ($ins->rowCount() === 1) {
            $pdo->prepare(
                'UPDATE users SET rating_count = (SELECT COUNT(*) FROM ratings WHERE rated_user_id = ?),
                                   rating_avg = (SELECT AVG(rating) FROM ratings WHERE rated_user_id = ?)
                 WHERE id = ?'
            )->execute([$driverId, $driverId, $driverId]);
        }

        header('Location: /musteri/elan/' . $listingId);
    }
}
