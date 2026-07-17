<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Billing;
use App\Core\DB;
use App\Payments\PayriffProvider;

/**
 * Payriff callback (bölmə 8). TƏHLÜKƏSİZLİK QAYDASI: callback body-sinə HEÇ VAXT
 * etibar edilmir — status Payriff-dən YENİDƏN sorğulanır. Idempotent: artıq 'paid'
 * olan ödəniş təkrar emal olunmur (paid_until ikiqat uzadılmasın deyə).
 */
final class PaymentCallbackController
{
    public function handle(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $orderId = (string) ($body['orderId'] ?? $body['order_id'] ?? '');

        if ($orderId === '') {
            http_response_code(400);
            echo 'orderId yoxdur';
            return;
        }

        $stmt = DB::conn()->prepare('SELECT * FROM payments WHERE payriff_order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $payment = $stmt->fetch();

        if ($payment === false) {
            http_response_code(404);
            echo 'Ödəniş tapılmadı';
            return;
        }

        if ($payment['status'] === 'paid') {
            // Idempotent: artıq emal olunub, callback-i sadəcə OK ilə qəbul edirik.
            http_response_code(200);
            echo 'OK';
            return;
        }

        $this->reconcile($payment);

        http_response_code(200);
        echo 'OK';
    }

    /** cron/payriff_recheck.php də bu metoddan istifadə edir. */
    public function reconcile(array $payment): void
    {
        $provider = new PayriffProvider();
        $result = $provider->getOrderStatus((string) $payment['payriff_order_id']);

        if (!$result['ok']) {
            return;
        }

        $rawJson = json_encode($result['raw'] ?? $result, JSON_UNESCAPED_UNICODE);

        if ($result['status'] === 'approved') {
            $pdo = DB::conn();
            $pdo->beginTransaction();
            try {
                // Yenidən oxu + kilidlə (eyni vaxtda callback + cron toqquşmasın deyə).
                $lockStmt = $pdo->prepare('SELECT status FROM payments WHERE id = ? FOR UPDATE');
                $lockStmt->execute([$payment['id']]);
                $current = $lockStmt->fetch();

                if ($current !== false && $current['status'] !== 'paid') {
                    $pdo->prepare("UPDATE payments SET status = 'paid', raw_response = ? WHERE id = ?")
                        ->execute([$rawJson, $payment['id']]);

                    $driverStmt = $pdo->prepare('SELECT paid_until FROM users WHERE id = ? FOR UPDATE');
                    $driverStmt->execute([$payment['driver_id']]);
                    $driver = $driverStmt->fetch();
                    $newPaidUntil = Billing::extendPaidUntil($driver['paid_until'] ?? null);

                    $pdo->prepare("UPDATE users SET billing_status = 'paid', paid_until = ? WHERE id = ?")
                        ->execute([$newPaidUntil, $payment['driver_id']]);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        } elseif (in_array($result['status'], ['declined', 'canceled'], true)) {
            $upd = DB::conn()->prepare("UPDATE payments SET status = 'failed', raw_response = ? WHERE id = ?");
            $upd->execute([$rawJson, $payment['id']]);
        }
        // 'pending'/'unknown' qalan hallarda heç nə dəyişmir — sonrakı recheck-də yenidən yoxlanılır.
    }
}
