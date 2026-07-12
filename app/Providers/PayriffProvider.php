<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Env;
use App\Core\Logger;

/**
 * Payriff adapteri — real API (https://api.payriff.com/api/v2/createOrder).
 *
 * QEYD: rəsmi sənədləşmə (docs.payriff.com) bu mühitdən şəbəkə siyasətinə görə
 * əlçatan deyildi — createOrder sorğu/cavab formatı Payriff-in rəsmi nümunə
 * reposundan (github.com/payriff-com/payriff-examples) və istifadəçinin
 * paylaşdığı əsl callback payload nümunəsindən tərtib olunub. approveURL,
 * cancelURL və declineURL QƏSDƏN eyni URL-ə (`/odenis/qayit`) göstərir —
 * Payriff dəstəyinin təsdiqinə görə "callback və return URL eynidir": Payriff
 * ödənişdən sonra HƏM bu URL-ə POST payload göndərir, HƏM DƏ müştərini paralel
 * şəkildə bura yönləndirir; nəticə real vəziyyəti `payload.paymentStatus`
 * sahəsindən oxunur, hansı URL-ə düşməsindən asılı olmayaraq.
 *
 * Callback-də açıq imza/HMAC sahəsi sənədləşdirilməyib — doğrulama üçün
 * order_id-nin təxmin edilə bilməyən UUID olması (Payriff tərəfindən
 * yaradılır) + OdenisService-də məbləğ üst-üstə düşmə yoxlaması (bax
 * OdenisService::webhookIsle) əsas müdafiə xəttidir.
 */
final class PayriffProvider implements PaymentProvider
{
    private const BASE_URL = 'https://api.payriff.com/api/v2';

    public function baslat(int $kuryeId, float $mebleg, string $orderId): PaymentSession
    {
        $merchant = Env::get('PAYRIFF_MERCHANT_ID', '');
        $secretKey = Env::get('PAYRIFF_SECRET_KEY', '');
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $qayitUrl = $appUrl . '/odenis/qayit';

        $govde = [
            'amount' => round($mebleg, 2),
            'currencyType' => 'AZN',
            'description' => 'Birlikdə kuryer abunə haqqı',
            'language' => 'AZ',
            'approveURL' => $qayitUrl,
            'cancelURL' => $qayitUrl,
            'declineURL' => $qayitUrl,
            'merchant' => $merchant,
        ];

        $cavab = self::sorguGonder('POST', '/createOrder', $govde, $secretKey);

        if (($cavab['code'] ?? null) !== '00000') {
            Logger::error('payriff_createorder_ugursuz', ['cavab' => $cavab]);
            throw new \RuntimeException('Payriff ödəniş sessiyası yaradıla bilmədi.');
        }

        $payriffOrderId = (string) ($cavab['payload']['orderId'] ?? '');
        $odenisUrl = (string) ($cavab['payload']['paymentUrl'] ?? '');

        if ($payriffOrderId === '' || $odenisUrl === '') {
            Logger::error('payriff_createorder_bos_cavab', ['cavab' => $cavab]);
            throw new \RuntimeException('Payriff ödəniş sessiyası yaradıla bilmədi.');
        }

        return new PaymentSession($payriffOrderId, $odenisUrl);
    }

    public function callbackDogrula(array $data): PaymentResult
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
        $orderId = (string) ($payload['orderId'] ?? '');
        $status = (string) ($payload['paymentStatus'] ?? '');
        $kod = (string) ($data['code'] ?? '');

        if ($orderId === '') {
            return new PaymentResult(false, false, $orderId, $data);
        }

        $gecerli = $kod === '00000';

        return new PaymentResult($gecerli, $gecerli && $status === 'PAID', $orderId, $data);
    }

    /**
     * Sifarişin cari vəziyyətini Payriff-dən server-tərəfdə sorğulayır —
     * callback-in özünü tam etibarlı hesab etməmək üçün əlavə təsdiq imkanı.
     * QEYD: dəqiq endpoint yolu (GET/POST, path) rəsmi sənədlərdən təsdiqlənə
     * bilmədi — bax sinif başlığındakı qeyd. Hazırda İSTİFADƏ OLUNMUR (yalnız
     * callback-ə etibar edilir); real sənəd əldə ediləndə bu metod
     * OdenisService-də əlavə təsdiq addımı kimi qoşulmalıdır.
     */
    public function seNehVeziyyet(string $payriffOrderId): array
    {
        $secretKey = Env::get('PAYRIFF_SECRET_KEY', '');

        return self::sorguGonder('GET', '/orders/' . rawurlencode($payriffOrderId), null, $secretKey);
    }

    /**
     * @param array<string, mixed>|null $govde
     * @return array<string, mixed>
     */
    private static function sorguGonder(string $metod, string $yol, ?array $govde, string $secretKey): array
    {
        $ch = curl_init(self::BASE_URL . $yol);

        $basliqlar = [
            'Content-Type: application/json',
            'Authorization: ' . $secretKey,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metod,
            CURLOPT_HTTPHEADER => $basliqlar,
            CURLOPT_TIMEOUT => 20,
        ]);

        if ($govde !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($govde, JSON_UNESCAPED_UNICODE));
        }

        $ham = curl_exec($ch);
        $xeta = curl_error($ch);
        curl_close($ch);

        if ($ham === false) {
            Logger::error('payriff_curl_xetasi', ['xeta' => $xeta, 'yol' => $yol]);
            throw new \RuntimeException('Payriff ilə əlaqə qurula bilmədi: ' . $xeta);
        }

        $decoded = json_decode((string) $ham, true);

        return is_array($decoded) ? $decoded : [];
    }
}
