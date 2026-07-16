<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Abunə/ödəniş qaydaları (bölmə 7.6, Q-Y7, Q-Y8). Admin panelindəki
 * payments_enabled toggle-i və fərdi/ümumi qiymət dəyişiklikləri buradan keçir.
 */
final class Billing
{
    public static function amountFor(array $driver): float
    {
        if ($driver['custom_price'] !== null) {
            return (float) $driver['custom_price'];
        }
        return (float) Settings::get('default_monthly_price', '25.00');
    }

    /**
     * Q-Y7: ödəniş açarı 0→1 keçidi. TƏMİZ HƏLL (sənəddəki dəqiq ifadə): mövcud
     * trial/paid aktiv sürücülərə TOXUNULMUR; yalnız artıq "expired" sayılanlara
     * (billing_status='expired', ya da trial/paid müddəti keçmiş) grace verilir:
     * trial_until = CURDATE()+grace_days, billing_status='trial'.
     *
     * @return int[] Push bildirişi göndəriləcək sürücü ID-ləri
     */
    public static function applyGraceOnEnable(): array
    {
        $graceDays = (int) Settings::get('grace_days', '7');

        $stmt = DB::conn()->prepare(
            "SELECT id FROM users
             WHERE role = 'driver' AND (
                billing_status = 'expired'
                OR (billing_status = 'trial' AND trial_until < CURDATE())
                OR (billing_status = 'paid' AND paid_until < CURDATE())
             )"
        );
        $stmt->execute();
        $driverIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if ($driverIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($driverIds), '?'));
        $upd = DB::conn()->prepare(
            "UPDATE users SET billing_status = 'trial', trial_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
             WHERE id IN ({$placeholders})"
        );
        $upd->execute([$graceDays, ...$driverIds]);

        return $driverIds;
    }

    /** Yeni paid_until: bugün ilə mövcud paid_until-dan böyüyündən +1 ay (ardıcıl ödənişlər üst-üstə düşməsin). */
    public static function extendPaidUntil(?string $currentPaidUntil): string
    {
        $base = $currentPaidUntil !== null && $currentPaidUntil >= date('Y-m-d') ? $currentPaidUntil : date('Y-m-d');
        return date('Y-m-d', strtotime($base . ' +1 month'));
    }
}
