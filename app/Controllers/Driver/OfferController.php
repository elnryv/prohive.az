<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\Sse;
use App\Core\WebPush;

final class OfferController
{
    private const MAX_OFFERS_PER_HOUR = 20;
    private const MAX_NOTE_LEN = 300;

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
        $user = Auth::user();

        if ($user['driver_status'] !== 'approved' || !Auth::isActiveDriver($user)) {
            header('Location: /surucu/elan/' . $listingId);
            return;
        }

        $price = (float) str_replace(',', '.', (string) ($_POST['price'] ?? '0'));
        $note = trim((string) ($_POST['note'] ?? ''));
        if (mb_strlen($note) > self::MAX_NOTE_LEN) {
            $note = mb_substr($note, 0, self::MAX_NOTE_LEN);
        }

        if ($price <= 0) {
            header('Location: /surucu/elan/' . $listingId . '?xeta=qiymet');
            return;
        }

        // Anti-spam: saatda maks 20 təklif (bölmə 7.3)
        $countStmt = DB::conn()->prepare(
            'SELECT COUNT(*) c FROM offers WHERE driver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $countStmt->execute([$driverId]);
        if ((int) $countStmt->fetch()['c'] >= self::MAX_OFFERS_PER_HOUR) {
            header('Location: /surucu/elan/' . $listingId . '?xeta=limit');
            return;
        }

        $listingStmt = DB::conn()->prepare("SELECT status FROM listings WHERE id = ? LIMIT 1");
        $listingStmt->execute([$listingId]);
        $listing = $listingStmt->fetch();
        if ($listing === false || $listing['status'] !== 'active') {
            header('Location: /surucu/elan/' . $listingId);
            return;
        }

        $existingStmt = DB::conn()->prepare('SELECT id FROM offers WHERE listing_id = ? AND driver_id = ? LIMIT 1');
        $existingStmt->execute([$listingId, $driverId]);
        $existing = $existingStmt->fetch();

        if ($existing !== false) {
            $upd = DB::conn()->prepare("UPDATE offers SET price = ?, note = ?, status = 'pending' WHERE id = ?");
            $upd->execute([$price, $note !== '' ? $note : null, $existing['id']]);
            $event = 'offer_updated';
        } else {
            $ins = DB::conn()->prepare(
                "INSERT INTO offers (listing_id, driver_id, price, note, status) VALUES (?, ?, ?, ?, 'pending')"
            );
            $ins->execute([$listingId, $driverId, $price, $note !== '' ? $note : null]);
            $incr = DB::conn()->prepare('UPDATE listings SET offers_count = offers_count + 1 WHERE id = ?');
            $incr->execute([$listingId]);
            $event = 'offer_new';
        }

        $ev = DB::conn()->prepare('INSERT INTO listing_events (listing_id, event, actor_user_id, details) VALUES (?, ?, ?, ?)');
        $ev->execute([$listingId, $event, $driverId, json_encode(['price' => $price], JSON_UNESCAPED_UNICODE)]);

        $customerStmt = DB::conn()->prepare('SELECT customer_id FROM listings WHERE id = ?');
        $customerStmt->execute([$listingId]);
        $customerId = (int) $customerStmt->fetch()['customer_id'];
        Sse::publish('customer_' . $customerId, $event, ['listing_id' => $listingId, 'price' => $price]);

        WebPush::sendToUsersLocalized([$customerId], static fn () => [
            'title' => t('push.offer_new_title'),
            'body' => number_format($price, 2) . ' AZN',
            'url' => '/musteri/elan/' . $listingId,
        ]);

        header('Location: /surucu/elan/' . $listingId . '?teklif=ok');
    }

    public function withdraw(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $driverId = (int) Auth::id();

        $stmt = DB::conn()->prepare(
            "UPDATE offers SET status = 'withdrawn' WHERE listing_id = ? AND driver_id = ? AND status = 'pending'"
        );
        $stmt->execute([$listingId, $driverId]);

        header('Location: /surucu/elan/' . $listingId);
    }

    /**
     * Q-Y5 sürücü tərəfi: qəbul edilmiş iş sürücü tərəfindən baş tutmursa, elan
     * yenidən aktivləşir (bax Customer\OfferActionController::cancel() — eyni
     * yenidən-açma qaydası, fərq yalnız kimin başlatdığı və kimin cancel_count-unun
     * artmasındadır).
     */
    public function cancelAccepted(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $driverId = (int) Auth::id();

        $pdo = DB::conn();
        $customerId = null;
        $restoredDriverIds = [];
        $ok = false;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "SELECT l.customer_id, o.id as offer_id FROM listings l
                 JOIN offers o ON o.id = l.accepted_offer_id
                 WHERE l.id = ? AND l.status = 'accepted' AND o.driver_id = ? FOR UPDATE"
            );
            $stmt->execute([$listingId, $driverId]);
            $row = $stmt->fetch();

            if ($row !== false) {
                $customerId = (int) $row['customer_id'];

                $newExpiresAt = ListingRules::reopenExpiresAt();
                $u1 = $pdo->prepare(
                    "UPDATE listings SET status = 'active', accepted_offer_id = NULL, expires_at = ?, reopen_count = reopen_count + 1 WHERE id = ?"
                );
                $u1->execute([$newExpiresAt, $listingId]);

                $pdo->prepare("UPDATE offers SET status = 'canceled_by_driver' WHERE id = ?")->execute([$row['offer_id']]);

                $restoreStmt = $pdo->prepare("SELECT driver_id FROM offers WHERE listing_id = ? AND status = 'lost'");
                $restoreStmt->execute([$listingId]);
                $restoredDriverIds = array_map('intval', array_column($restoreStmt->fetchAll(), 'driver_id'));
                $pdo->prepare("UPDATE offers SET status = 'pending' WHERE listing_id = ? AND status = 'lost'")->execute([$listingId]);

                $pdo->prepare('UPDATE users SET cancel_count = cancel_count + 1 WHERE id = ?')->execute([$driverId]);

                $ev = $pdo->prepare(
                    'INSERT INTO listing_events (listing_id, event, actor_user_id) VALUES (?, "reopened", ?)'
                );
                $ev->execute([$listingId, $driverId]);

                $ok = true;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if (!$ok) {
            header('Location: /surucu/tekliflerim?tab=accepted');
            return;
        }

        $card = ListingRules::renderFeedCard($listingId);
        $reopenPayload = [
            'listing_id' => $listingId,
            'scope' => $card['scope'] ?? null,
            'html' => $card['html'] ?? null,
            'from_location_id' => $card['from_location_id'] ?? null,
            'to_location_id' => $card['to_location_id'] ?? null,
        ];
        Sse::publish('feed', 'listing_reopened', $reopenPayload);
        Sse::publish('customer_' . $customerId, 'listing_reopened', $reopenPayload);
        foreach ($restoredDriverIds as $did) {
            Sse::publish('driver_' . $did, 'listing_reopened', $reopenPayload);
        }

        WebPush::sendToUsersLocalized([$customerId], static fn () => [
            'title' => t('push.driver_canceled_title'),
            'body' => t('push.driver_canceled_body'),
            'url' => '/musteri/elan/' . $listingId,
        ], 'high');

        header('Location: /surucu/tekliflerim?tab=accepted');
    }
}
