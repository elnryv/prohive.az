<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Config;

/**
 * Payriff API v3 inteqrasiyası (bölmə 8). SECRET_KEY/merchant_id boş olduqda sistem
 * yıxılmır — sadəcə "ödəniş tezliklə" vəziyyətinə düşür (isConfigured() === false).
 *
 * Sorğu/cavab formatı (Authorization başlığı xam Secret Key olaraq, POST /api/v3/orders,
 * GET /api/v3/orders/{orderId}, payload.orderId/paymentUrl, payload.paymentStatus ??
 * payload.orderStatus) BIRLIKDƏ GETDİK layihəsinin eyni müəllif tərəfindən yazılmış TAM
 * PayriffProvider.php-sindən (bölmə 8.3) götürülüb — bu sənəd Payriff v3-ün konkret
 * sorğu/cavab konvensiyalarını göstərən yeganə mövcud mənbədir. Callback təhlükəsizlik
 * qaydası (statusu Payriff-dən yenidən sorğulamaq, heç vaxt callback body-sinə etibar
 * etməmək) həmin sənədə uyğun tətbiq olunub.
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
            'amount' => round($amount, 2),
            'language' => 'AZ',
            'currency' => $currency,
            'description' => mb_substr($description, 0, 250),
            'callbackUrl' => Config::get('payriff.callback_url'),
            'cancelUrl' => Config::get('payriff.return_url'),
            'operation' => 'PURCHASE',
            'metadata' => ['externalRef' => $externalTrackId],
        ];

        $response = $this->request('POST', '/orders', $body);
        $payload = $response['payload'] ?? [];
        if ($response === null || empty($payload['orderId']) || empty($payload['paymentUrl'])) {
            $this->log('createOrder FAIL', $response);
            return ['ok' => false, 'error' => $response['message'] ?? 'request_failed'];
        }

        return [
            'ok' => true,
            'order_id' => (string) $payload['orderId'],
            'payment_url' => (string) $payload['paymentUrl'],
        ];
    }

    public function getOrderStatus(string $orderId): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'status' => 'unknown'];
        }

        $response = $this->request('GET', '/orders/' . rawurlencode($orderId), null);
        if ($response === null) {
            return ['ok' => false, 'status' => 'unknown'];
        }

        $payload = $response['payload'] ?? [];
        $status = strtoupper((string) ($payload['paymentStatus'] ?? $payload['orderStatus'] ?? 'UNKNOWN'));
        $status = in_array($status, ['APPROVED', 'DECLINED', 'CANCELED', 'PENDING'], true) ? $status : 'UNKNOWN';

        return ['ok' => true, 'status' => strtolower($status), 'raw' => $response];
    }

    /** @return array<string,mixed>|null */
    private function request(string $method, string $path, ?array $body): ?array
    {
        $baseUrl = rtrim((string) Config::get('payriff.base_url'), '/');
        $ch = curl_init($baseUrl . $path);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . Config::get('payriff.secret_key'),
        ];

        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            $this->log("cURL error [$method $path]: $err");
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->log("Bad JSON [$method $path, $code]: $raw");
            return null;
        }

        $this->log("$method $path [$code]", $decoded);
        return $decoded;
    }

    private function log(string $msg, mixed $ctx = null): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = date('c') . ' ' . $msg . ($ctx !== null ? ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) : '') . "\n";
        @file_put_contents($dir . '/payriff.log', $line, FILE_APPEND | LOCK_EX);
    }
}
