<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;

/** Tamamlanmış işdən sonra sürücünün müştəriyə verdiyi reytinq. */
final class RatingController
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
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            header('Location: /surucu/tarixce');
            return;
        }

        $stmt = DB::conn()->prepare(
            "SELECT l.customer_id FROM listings l
             JOIN offers o ON o.id = l.accepted_offer_id
             WHERE l.id = ? AND o.driver_id = ? AND l.status = 'completed' LIMIT 1"
        );
        $stmt->execute([$listingId, $driverId]);
        $row = $stmt->fetch();

        if ($row === false) {
            header('Location: /surucu/tarixce');
            return;
        }

        $customerId = (int) $row['customer_id'];

        $pdo = DB::conn();
        $ins = $pdo->prepare(
            'INSERT IGNORE INTO ratings (listing_id, rater_user_id, rated_user_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$listingId, $driverId, $customerId, $rating, $comment !== '' ? $comment : null]);

        if ($ins->rowCount() === 1) {
            $pdo->prepare(
                'UPDATE users SET rating_count = (SELECT COUNT(*) FROM ratings WHERE rated_user_id = ?),
                                   rating_avg = (SELECT AVG(rating) FROM ratings WHERE rated_user_id = ?)
                 WHERE id = ?'
            )->execute([$customerId, $customerId, $customerId]);
        }

        header('Location: /surucu/tarixce');
    }
}
