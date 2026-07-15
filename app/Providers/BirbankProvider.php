<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Env;

/**
 * Birbank adapteri — bax CLAUDE.md bölmə 9.1 (Əlavə B: "9.1 Payment adapterini
 * Birbank üçün tətbiq et").
 *
 * QEYD: Birbank-ın real API sənədləşməsi hələ mövcud deyil (bax roadmap: "Birbank/
 * Payriff sonra qoşulur"). Aşağıdakı sahə adları/URL bank tərəfindən təsdiqlənənə
 * qədər YER TUTUCUDUR — struktur (imzalanmış yönləndirmə linki + HMAC-SHA256 ilə
 * webhook doğrulama) hosted-checkout tipli provayderlər üçün ümumi standart nümunədir.
 */
final class BirbankProvider implements PaymentProvider
{
    public function baslat(int $kuryeId, float $mebleg, string $orderId): PaymentSession
    {
        $merchantId = Env::get('BIRBANK_MERCHANT_ID', '');
        $secretKey = Env::get('BIRBANK_SECRET_KEY', '');
        $baseUrl = Env::get('BIRBANK_PAYMENT_URL', '');
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

        $merchantId = Env::get('BIRBANK_MERCHANT_ID', '');
        $secretKey = Env::get('BIRBANK_SECRET_KEY', '');

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
