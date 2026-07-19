<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class PayriffException extends RuntimeException
{
}

function payriff_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = (require __DIR__ . '/config.php')['payriff'];
    }
    return $config;
}

function payriff_configured(): bool
{
    return payriff_config()['secret_key'] !== '';
}

function payriff_request(string $method, string $path, array $body = []): array
{
    $cfg = payriff_config();
    if ($cfg['secret_key'] === '') {
        throw new PayriffException('Payriff konfiqurasiya edilməyib (PAYRIFF_SECRET_KEY boşdur).');
    }

    $ch = curl_init(rtrim($cfg['base_url'], '/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . $cfg['secret_key'],
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new PayriffException("Payriff sorğusu uğursuz oldu: {$error}");
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new PayriffException('Payriff cavabı düzgün JSON deyil.');
    }
    if ($status >= 400) {
        throw new PayriffException($decoded['message'] ?? "Payriff xətası (HTTP {$status}).");
    }

    return $decoded;
}

// V3 createOrder — https://docs.payriff.com/ (POST /api/v3/{method}, Authorization
// header = merchant secret key). Ödəniş uğurla tamamlandıqda Payriff callbackUrl-ə
// POST edir, approveURL/cancelURL/declineURL isə istifadəçinin brauzerini yönləndirir.
function payriff_create_order(
    float $amount,
    string $currency,
    string $description,
    string $callbackUrl,
    string $approveUrl,
    string $cancelUrl,
    string $declineUrl
): array {
    $result = payriff_request('POST', '/api/v3/createOrder', [
        'amount' => round($amount, 2),
        'language' => 'AZ',
        'currency' => $currency,
        'description' => $description,
        'callbackUrl' => $callbackUrl,
        'approveURL' => $approveUrl,
        'cancelURL' => $cancelUrl,
        'declineURL' => $declineUrl,
        'operation' => 'PURCHASE',
    ]);

    $payload = $result['payload'] ?? $result;
    if (empty($payload['orderId']) || empty($payload['paymentUrl'])) {
        throw new PayriffException('Payriff cavabında orderId/paymentUrl yoxdur.');
    }

    return ['order_id' => (string) $payload['orderId'], 'payment_url' => (string) $payload['paymentUrl']];
}

// Sifarişin həqiqi statusunu Payriff-dən (gizli açarımızla) soruşur. Webhook
// bədəninə etibar etmirik — orderId-ni bu funksiya ilə serverdən təsdiqlədikdən
// sonra abunə aktivləşdirilir, ona görə saxta callback sorğusu zərərsizdir.
function payriff_get_order_status(string $orderId): string
{
    $result = payriff_request('POST', '/api/v3/getOrderInformation', ['orderId' => $orderId]);
    $payload = $result['payload'] ?? $result;
    return strtoupper((string) ($payload['status'] ?? $payload['orderStatus'] ?? 'UNKNOWN'));
}

function payriff_status_is_success(string $status): bool
{
    return in_array($status, ['APPROVED', 'SUCCESS', 'PAID', 'FULLY_PAID'], true);
}

function payriff_status_is_failed(string $status): bool
{
    return in_array($status, ['DECLINED', 'FAILED', 'CANCELED', 'CANCELLED', 'EXPIRED'], true);
}
