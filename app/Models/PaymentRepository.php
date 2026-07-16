<?php
declare(strict_types=1);

/**
 * Abunə ödənişləri (bölmə 7.6, 8.4, 8.5, Q10).
 * Məbləğ yaradılan anda FİKSƏ olunur — sonrakı qiymət dəyişiklikləri
 * mövcud (artıq yaradılmış) ödəniş sətirlərinə təsir etmir.
 */
final class PaymentRepository
{
    public static function create(int $ownerId, float $amount, int $months = 1): int
    {
        DB::query(
            "INSERT INTO payments (owner_id, amount, currency, months, status, created_at)
             VALUES (:owner_id, :amount, 'AZN', :months, 'created', NOW())",
            [':owner_id' => $ownerId, ':amount' => $amount, ':months' => $months]
        );
        return (int) DB::lastInsertId();
    }

    public static function attachOrder(int $paymentId, string $orderId, string $paymentUrl): void
    {
        DB::query(
            'UPDATE payments SET payriff_order_id = :oid, payment_url = :url WHERE id = :id',
            [':oid' => $orderId, ':url' => $paymentUrl, ':id' => $paymentId]
        );
    }

    public static function findById(int $id): ?array
    {
        return DB::one('SELECT * FROM payments WHERE id = :id', [':id' => $id]);
    }

    public static function findByOrderId(string $orderId): ?array
    {
        return DB::one('SELECT * FROM payments WHERE payriff_order_id = :oid', [':oid' => $orderId]);
    }

    public static function saveRawResponse(int $paymentId, array $response): void
    {
        DB::query(
            'UPDATE payments SET raw_response = :r WHERE id = :id',
            [':r' => json_encode($response, JSON_UNESCAPED_UNICODE), ':id' => $paymentId]
        );
    }

    /**
     * 8.4: payments.status='approved' + owners.paid_until/billing_status yenilənir.
     * Atomik + idempotent — UPDATE ... WHERE status='created' sətri kilidləyir,
     * təkrar çağırışlarda (təkrar callback) rowCount=0 olur, heç nə dəyişmir.
     * @return bool true = bu çağırış faktiki tətbiq etdi, false = artıq tətbiq olunmuşdu
     */
    public static function applyApproval(int $paymentId): bool
    {
        DB::beginTransaction();
        try {
            $stmt = DB::query(
                "UPDATE payments SET status = 'approved', paid_at = NOW() WHERE id = :id AND status = 'created'",
                [':id' => $paymentId]
            );
            if ($stmt->rowCount() === 0) {
                DB::commit();
                return false;
            }

            DB::query(
                "UPDATE owners o
                 JOIN payments p ON p.owner_id = o.id
                 SET o.paid_until = GREATEST(COALESCE(o.paid_until, o.trial_until, CURDATE()), CURDATE()) + INTERVAL p.months MONTH,
                     o.billing_status = 'paid'
                 WHERE p.id = :id",
                [':id' => $paymentId]
            );

            DB::commit();

            $payment = self::findById($paymentId);
            if ($payment !== null) {
                $payload = ['payment_id' => $paymentId, 'amount' => $payment['amount']];
                Sse::emit('owner_' . $payment['owner_id'], 'payment_ok', $payload);
                Sse::emit('admin', 'payment_ok', $payload + ['owner_id' => (int) $payment['owner_id']]);
            }

            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function markStatus(int $paymentId, string $status): void
    {
        DB::query(
            'UPDATE payments SET status = :status WHERE id = :id AND status = "created"',
            [':status' => $status, ':id' => $paymentId]
        );
    }

    /** @return array<int, array<string,mixed>> recheck cron üçün: 'created', 48 saatdan gənc */
    public static function staleCreated(): array
    {
        return DB::all(
            "SELECT * FROM payments WHERE status = 'created' AND created_at > NOW() - INTERVAL 48 HOUR"
        );
    }

    /** 48 saatdan köhnə 'created' sətirləri 'expired' edir @return int təsirlənən sətir sayı */
    public static function expireOldCreated(): int
    {
        $stmt = DB::query(
            "UPDATE payments SET status = 'expired' WHERE status = 'created' AND created_at <= NOW() - INTERVAL 48 HOUR"
        );
        return $stmt->rowCount();
    }
}
