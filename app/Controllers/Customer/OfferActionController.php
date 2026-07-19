<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\Sse;
use App\Core\WebPush;
use PDO;

/**
 * Q-Y3 (nömrə məxfiliyi) + Q-Y4 (atomik qəbul) + Q-Y5 (ləğv/yenidən açılma).
 * Bu fayl layihənin "ürəyi"dir — dəyişiklik edilərkən xüsusi diqqətlə davranılmalıdır.
 */
final class OfferActionController
{
    private const MAX_REOPEN = 2;

    /**
     * Q-Y4: "Qəbul et" = atomik razılaşma anı. Bölmə 6.4-dəki tranzaksiya BİRƏ-BİR.
     */
    public function accept(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $offerId = (int) $params['offerId'];
        $customerId = (int) Auth::id();

        // Elanın bu müştəriyə aid olduğunu əvvəlcədən yoxlayırıq (mülkiyyət).
        $ownerCheck = DB::conn()->prepare('SELECT id FROM listings WHERE id = ? AND customer_id = ? LIMIT 1');
        $ownerCheck->execute([$listingId, $customerId]);
        if ($ownerCheck->fetch() === false) {
            http_response_code(403);
            echo 'Bu elan sənə aid deyil.';
            return;
        }

        $pdo = DB::conn();
        $accepted = false;
        $driverId = null;

        $pdo->beginTransaction();
        try {
            // Yarış vəziyyətinə qarşı: FOR UPDATE ilə sətir kilidlənir.
            $stmt = $pdo->prepare('SELECT status FROM listings WHERE id = ? FOR UPDATE');
            $stmt->execute([$listingId]);
            $listing = $stmt->fetch();

            if ($listing !== false && $listing['status'] === 'active') {
                $offerStmt = $pdo->prepare(
                    "SELECT driver_id FROM offers WHERE id = ? AND listing_id = ? AND status = 'pending' LIMIT 1"
                );
                $offerStmt->execute([$offerId, $listingId]);
                $offer = $offerStmt->fetch();

                if ($offer !== false) {
                    $driverId = (int) $offer['driver_id'];

                    $u1 = $pdo->prepare(
                        "UPDATE listings SET status = 'accepted', accepted_offer_id = ?, accepted_at = NOW() WHERE id = ? AND status = 'active'"
                    );
                    $u1->execute([$offerId, $listingId]);

                    if ($u1->rowCount() === 1) {
                        $u2 = $pdo->prepare("UPDATE offers SET status = 'accepted' WHERE id = ? AND listing_id = ?");
                        $u2->execute([$offerId, $listingId]);

                        $u3 = $pdo->prepare(
                            "UPDATE offers SET status = 'lost' WHERE listing_id = ? AND id <> ? AND status = 'pending'"
                        );
                        $u3->execute([$listingId, $offerId]);

                        $ev = $pdo->prepare(
                            'INSERT INTO listing_events (listing_id, event, actor_user_id, details) VALUES (?, "accepted", ?, ?)'
                        );
                        $ev->execute([$listingId, $customerId, json_encode(['offer_id' => $offerId], JSON_UNESCAPED_UNICODE)]);

                        $accepted = true;
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        if (!$accepted) {
            // Başqası artıq qəbul edib və ya elan aktiv deyil — istifadəçiyə cari vəziyyəti göstəririk.
            header('Location: /musteri/elan/' . $listingId . '?xeta=artiq_qebul_edilib');
            return;
        }

        // Tranzaksiyadan SONRA (bölmə 6.4, addım 1-5):
        Sse::publish('feed', 'listing_closed', ['listing_id' => $listingId]);
        Sse::publish('driver_' . $driverId, 'offer_accepted', ['listing_id' => $listingId]);
        Sse::publish('customer_' . $customerId, 'accepted', ['listing_id' => $listingId]);

        WebPush::sendToUsersLocalized([$driverId], static fn () => [
            'title' => t('push.offer_accepted_title'),
            'body' => t('push.offer_accepted_body'),
            'url' => '/surucu/tekliflerim',
        ], 'high');

        header('Location: /musteri/elan/' . $listingId);
    }

    /**
     * Q-Y5: "Baş tutmadı" — elan yenidən aktivləşir, ləğv edilmiş təklif canceled_by_customer,
     * lost olanlar pending-ə qaytarılır. Maks 2 dəfə yenidən açıla bilər.
     */
    public function cancel(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $listingId = (int) $params['id'];
        $customerId = (int) Auth::id();
        $reason = (string) ($_POST['reason'] ?? 'changed_mind');
        if (!in_array($reason, ['driver_no_show', 'price_changed', 'changed_mind'], true)) {
            $reason = 'changed_mind';
        }

        $stmt = DB::conn()->prepare(
            'SELECT status, accepted_offer_id, reopen_count FROM listings WHERE id = ? AND customer_id = ? LIMIT 1'
        );
        $stmt->execute([$listingId, $customerId]);
        $listing = $stmt->fetch();

        // Q-Y5 genişlənməsi: razılaşma "tamamlandı" statusuna (cron/bölmə 6.7 ilə avtomatik
        // keçmiş ola bilər) çatandan sonra da faktiki baş tutmayıbsa, müştəri elanı yenidən
        // aça bilməlidir — bax OfferActionController::cancel() aşağıdakı jobs_done geri qaytarma.
        if ($listing === false || !in_array($listing['status'], ['accepted', 'completed'], true)) {
            header('Location: /musteri/elan/' . $listingId);
            return;
        }
        if ((int) $listing['reopen_count'] >= self::MAX_REOPEN) {
            header('Location: /musteri/elan/' . $listingId . '?xeta=limit_yenidenachma');
            return;
        }

        $wasCompleted = $listing['status'] === 'completed';

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $acceptedOfferId = $listing['accepted_offer_id'];

            $driverIdStmt = $pdo->prepare('SELECT driver_id FROM offers WHERE id = ?');
            $driverIdStmt->execute([$acceptedOfferId]);
            $acceptedDriverId = $driverIdStmt->fetch()['driver_id'] ?? null;

            $newExpiresAt = ListingRules::reopenExpiresAt();
            $u1 = $pdo->prepare(
                "UPDATE listings SET status = 'active', accepted_offer_id = NULL, completed_at = NULL, expires_at = ?, reopen_count = reopen_count + 1 WHERE id = ?"
            );
            $u1->execute([$newExpiresAt, $listingId]);

            $pdo->prepare("UPDATE offers SET status = 'canceled_by_customer' WHERE id = ?")->execute([$acceptedOfferId]);

            // Tamamlanmış iş faktiki baş tutmayıbsa, cron-un artırdığı jobs_done geri qaytarılır.
            if ($wasCompleted && $acceptedDriverId !== null) {
                $pdo->prepare(
                    'UPDATE users SET jobs_done = GREATEST(jobs_done - 1, 0) WHERE id = ?'
                )->execute([$acceptedDriverId]);
            }

            // Q-Y5: yenidən açılmada lost olanlar pending-ə QAYTARILIR.
            $restoreStmt = $pdo->prepare("SELECT driver_id FROM offers WHERE listing_id = ? AND status = 'lost'");
            $restoreStmt->execute([$listingId]);
            $restoredDriverIds = array_column($restoreStmt->fetchAll(PDO::FETCH_ASSOC), 'driver_id');

            $pdo->prepare("UPDATE offers SET status = 'pending' WHERE listing_id = ? AND status = 'lost'")->execute([$listingId]);

            if ($reason === 'driver_no_show' && $acceptedDriverId !== null) {
                $pdo->prepare('UPDATE users SET cancel_count = cancel_count + 1 WHERE id = ?')->execute([$acceptedDriverId]);
            }

            $ev = $pdo->prepare(
                'INSERT INTO listing_events (listing_id, event, actor_user_id, details) VALUES (?, "reopened", ?, ?)'
            );
            $ev->execute([$listingId, $customerId, json_encode(['reason' => $reason], JSON_UNESCAPED_UNICODE)]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Ləğv olunan təklifin sahibi də bildirilməlidir — o "lost" deyil,
        // "canceled_by_customer" statusundadır, ona görə $restoredDriverIds-ə
        // düşmür, amma yenidən açılan elana yenidən təklif verə bilər (bax
        // driver/listing_show.php-dəki status şərti) və bunu bilməlidir.
        $notifyDriverIds = array_map('intval', $restoredDriverIds);
        if ($acceptedDriverId !== null && !in_array((int) $acceptedDriverId, $notifyDriverIds, true)) {
            $notifyDriverIds[] = (int) $acceptedDriverId;
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
        foreach ($notifyDriverIds as $did) {
            Sse::publish('driver_' . $did, 'listing_reopened', $reopenPayload);
        }
        if ($notifyDriverIds !== []) {
            WebPush::sendToUsersLocalized($notifyDriverIds, static fn () => [
                'title' => t('push.listing_reopened_title'),
                'body' => t('push.listing_reopened_body'),
                'url' => '/surucu/lent',
            ]);
        }

        header('Location: /musteri/elan/' . $listingId);
    }
}
