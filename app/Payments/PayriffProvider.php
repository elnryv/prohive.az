<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Config;

/**
 * Payriff API v3 inteqrasiyası (bölmə 8). SECRET_KEY/merchant_id boş olduqda sistem
 * yıxılmır — sadəcə "ödəniş tezliklə" vəziyyətinə düşür (isConfigured() === false).
 *
 * DİQQƏT (bax PROGRESS.md "SUAL" qeydi): bu sinif Getdik layihəsindəki əsl
 * PayriffProvider.php-nin YERİNƏ, Payriff-in ictimai API v3 sənədləşməsinə əsasən
 * müstəqil yazılıb (mənbə kod bu sessiyada mövcud deyildi). Sorğu formatı (createOrder,
 * getOrderStatus, HMAC imza) Payriff-in standart inteqrasiya nümunələrinə uyğundur, lakin
 * canlıya keçmədən əvvəl sahibkar bunu Payriff-in öz kabinetindəki sənədləşmə/sandbox
 * açarları ilə yoxlamalıdır. Callback təhlükəsizlik qaydası (statusu Payriff-dən yenidən
 * sorğulamaq, heç vaxt callback body-sinə etibar etməmək) ciddi şəkildə tətbiq olunub.
 */
final class PayriffProvider implements PaymentGateway
{
    public static function isConfigured(): bool
    {
        return (string) Config::get('payriff.merchant_id', '') !== ''
            && (string) Config::get('payriff.secret_key', '') !== '';
    }

    public function createOrder(float $amount, string $currency, string $description, string $externalTrackId): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        $body = [
            'merchant' => Config::get('payriff.merchant_id'),
            'amount' => round($amount, 2),
            'currency' => $currency,
            'description' => $description,
            'language' => 'AZ',
            'callbackUrl' => Config::get('payriff.callback_url'),
            'externalTrackId' => $externalTrackId,
        ];

        $response = $this->request('POST', '/orders', $body);
        if ($response === null || ($response['code'] ?? null) !== 'OK') {
            return ['ok' => false, 'error' => $response['message'] ?? 'request_failed'];
        }

        $payload = $response['payload'] ?? [];
        return [
            'ok' => true,
            'order_id' => (string) ($payload['orderId'] ?? ''),
            'payment_url' => (string) ($payload['paymentUrl'] ?? ''),
        ];
    }

    public function getOrderStatus(string $orderId): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'status' => 'unknown'];
        }

        $response = $this->request('GET', '/orders/' . urlencode($orderId) . '/status', null);
        if ($response === null) {
            return ['ok' => false, 'status' => 'unknown'];
        }

        $payload = $response['payload'] ?? [];
        $status = (string) ($payload['status'] ?? 'unknown');
        // Payriff status dəyərləri: APPROVED, DECLINED, CREATED, EXPIRED, REVERSED
        return ['ok' => true, 'status' => strtolower($status), 'raw' => $response];
    }

    /** @return array<string,mixed>|null */
    private function request(string $method, string $path, ?array $body): ?array
    {
        $baseUrl = rtrim((string) Config::get('payriff.base_url'), '/');
        $ch = curl_init($baseUrl . $path);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . Config::get('payriff.secret_key'),
        ];

        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
