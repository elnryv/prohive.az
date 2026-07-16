<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Billing;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Settings;
use App\Core\View;
use App\Payments\PayriffProvider;

final class BillingController
{
    public function show(): void
    {
        Auth::requireRole('driver', '/giris');
        $user = Auth::user();

        $paymentsEnabled = Settings::get('payments_enabled', '1') === '1';

        $history = DB::conn()->prepare('SELECT * FROM payments WHERE driver_id = ? ORDER BY created_at DESC LIMIT 20');
        $history->execute([Auth::id()]);

        View::render('driver/billing', [
            'pageTitle' => t('nav.profile'),
            'paymentsEnabled' => $paymentsEnabled,
            'user' => $user,
            'amount' => Billing::amountFor($user),
            'currency' => Settings::get('currency', 'AZN'),
            'payriffConfigured' => PayriffProvider::isConfigured(),
            'history' => $history->fetchAll(),
        ]);
    }

    public function pay(): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        if (Settings::get('payments_enabled', '1') !== '1') {
            header('Location: /surucu/odenis');
            return;
        }

        if (!PayriffProvider::isConfigured()) {
            header('Location: /surucu/odenis?xeta=hazir_deyil');
            return;
        }

        $user = Auth::user();
        $amount = Billing::amountFor($user);
        $currency = Settings::get('currency', 'AZN');

        $insert = DB::conn()->prepare(
            "INSERT INTO payments (driver_id, amount, currency, status) VALUES (?, ?, ?, 'pending')"
        );
        $insert->execute([Auth::id(), $amount, $currency]);
        $paymentId = (int) DB::conn()->lastInsertId();

        $provider = new PayriffProvider();
        $result = $provider->createOrder($amount, $currency, 'Birlikdə Yük abunə', 'yuk-payment-' . $paymentId);

        if (!$result['ok']) {
            $fail = DB::conn()->prepare("UPDATE payments SET status = 'failed', raw_response = ? WHERE id = ?");
            $fail->execute([json_encode($result, JSON_UNESCAPED_UNICODE), $paymentId]);
            header('Location: /surucu/odenis?xeta=sifaris_alinmadi');
            return;
        }

        $upd = DB::conn()->prepare('UPDATE payments SET payriff_order_id = ?, payment_url = ? WHERE id = ?');
        $upd->execute([$result['order_id'], $result['payment_url'], $paymentId]);

        header('Location: ' . $result['payment_url']);
    }
}
