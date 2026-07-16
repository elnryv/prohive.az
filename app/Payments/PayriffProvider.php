<?php
declare(strict_types=1);

/** Payriff API v3 inteqrasiyası (bölmə 8.3). Açar boşdursa konstruktor xəta atır (Q7). */
final class PayriffProvider implements PaymentGateway
{
    private string $secret;
    private string $base;

    public function __construct()
    {
        $this->secret = PAYRIFF_SECRET_KEY;
        $this->base = rtrim(PAYRIFF_BASE_URL, '/');
        if ($this->secret === '') {
            throw new RuntimeException('PAYRIFF_SECRET_KEY doldurulmayıb');
        }
    }

    public function createOrder(float $amount, string $description, string $externalRef): array
    {
        $body = [
            'amount' => round($amount, 2),
            'language' => 'AZ',
            'currency' => 'AZN',
            'description' => mb_substr($description, 0, 250),
            'callbackUrl' => PAYRIFF_CALLBACK,
            'cancelUrl' => PAYRIFF_RESULT_NO,
            'operation' => 'PURCHASE',
            'metadata' => ['externalRef' => $externalRef],
        ];
        $res = $this->request('POST', '/api/v3/orders', $body);

        $payload = $res['payload'] ?? [];
        if (empty($payload['orderId']) || empty($payload['paymentUrl'])) {
            $this->log('createOrder FAIL', $res);
            throw new RuntimeException('Payriff sifariş yaradıla bilmədi');
        }

        return [
            'orderId' => (string) $payload['orderId'],
            'paymentUrl' => (string) $payload['paymentUrl'],
        ];
    }

    public function getOrderStatus(string $orderId): string
    {
        $res = $this->request('GET', '/api/v3/orders/' . rawurlencode($orderId));
        $status = strtoupper((string) ($res['payload']['paymentStatus'] ?? $res['payload']['orderStatus'] ?? 'UNKNOWN'));

        return in_array($status, ['APPROVED', 'DECLINED', 'CANCELED', 'PENDING'], true) ? $status : 'UNKNOWN';
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init($this->base . $path);
        $headers = [
            'Authorization: ' . $this->secret,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->log("cURL error: {$err}");
            throw new RuntimeException('Payriff bağlantı xətası');
        }

        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            $this->log("Bad JSON [{$code}]: {$raw}");
            throw new RuntimeException('Payriff cavabı düzgün formatda deyil');
        }

        $this->log("{$method} {$path} [{$code}]", $json);

        return $json;
    }

    private function log(string $msg, mixed $ctx = null): void
    {
        $line = date('c') . ' ' . $msg . ($ctx !== null ? ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) : '') . "\n";
        @file_put_contents(APP_ROOT . '/storage/logs/payriff.log', $line, FILE_APPEND | LOCK_EX);
    }
}
