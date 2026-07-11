<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Env;

/**
 * Payriff adapteri — bax CLAUDE.md bölmə 9.1.
 *
 * QEYD: Payriff-in real API sənədləşməsi hələ mövcud deyil (bax roadmap: "Birbank/
 * Payriff sonra qoşulur"). Struktur BirbankProvider ilə eynidir (imzalanmış
 * yönləndirmə + HMAC-SHA256 webhook doğrulama), yalnız konfiqurasiya açarları fərqlənir.
 */
final class PayriffProvider implements PaymentProvider
{
    public function baslat(int $kuryeId, float $mebleg, string $orderId): PaymentSession
    {
        $merchantId = Env::get('PAYRIFF_MERCHANT_ID', '');
        $secretKey = Env::get('PAYRIFF_SECRET_KEY', '');
        $baseUrl = Env::get('PAYRIFF_PAYMENT_URL', '');
        $mebleqStr = number_format($mebleg, 2, '.', '');
        $valyuta = 'AZN';

        $imza = self::imzaHesabla($merchantId, $orderId, $mebleqStr, $valyuta, $secretKey);

        $sorgu = http_build_query([
            'merchant' => $merchantId,
            'order_id' => $orderId,
            'amount' => $mebleqStr,
            'currency' => $valyuta,
            'signature' => $imza,
        ]);

        return new PaymentSession($orderId, $baseUrl . '?' . $sorgu);
    }

    public function callbackDogrula(array $data): PaymentResult
    {
        $orderId = (string) ($data['order_id'] ?? '');
        $status = (string) ($data['status'] ?? '');
        $mebleqStr = (string) ($data['amount'] ?? '');
        $valyuta = (string) ($data['currency'] ?? 'AZN');
        $gelenImza = (string) ($data['signature'] ?? '');

        $merchantId = Env::get('PAYRIFF_MERCHANT_ID', '');
        $secretKey = Env::get('PAYRIFF_SECRET_KEY', '');

        $gozlenilenImza = self::imzaHesabla($merchantId, $orderId, $mebleqStr, $valyuta, $secretKey);

        if ($orderId === '' || !hash_equals($gozlenilenImza, $gelenImza)) {
            return new PaymentResult(false, false, $orderId, $data);
        }

        return new PaymentResult(true, $status === 'ugurlu', $orderId, $data);
    }

    private static function imzaHesabla(string $merchantId, string $orderId, string $mebleg, string $valyuta, string $secretKey): string
    {
        $kanonik = implode('|', [$merchantId, $orderId, $mebleg, $valyuta]);

        return hash_hmac('sha256', $kanonik, $secretKey);
    }
}
