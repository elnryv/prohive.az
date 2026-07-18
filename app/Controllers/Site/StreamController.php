<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Sse;

/**
 * SSE axını (bölmə 11.5). Kanallar: feed (yalnız approved sürücü qoşulur),
 * driver_{id}, customer_{id} (yalnız sahibi qoşulur), admin (ayrıca AdminAuth ilə).
 */
final class StreamController
{
    public function feed(): void
    {
        Auth::requireRole('driver', '/giris');
        $user = Auth::user();
        if ($user['driver_status'] !== 'approved') {
            http_response_code(403);
            return;
        }
        $driverId = (int) Auth::id();
        // KRİTİK: `Last-Event-ID` başlığı `lastId` GET parametrindən ÖNCƏ oxunmalıdır.
        // Brauzerin native EventSource avtomatik yenidən-qoşulması (hər ~55s-lik Sse::stream
        // dövrü bitəndə baş verir) HƏMİŞƏ İLKİN URL-i təkrar istifadə edir — `lastId` GET
        // parametri səhifə yükləndiyi andakı DƏYƏRDƏ donub qalır, dəyişə bilmir. Amma brauzer
        // hər yenidən-qoşulmada DÜZGÜN, son görülən ID-ni `Last-Event-ID` başlığında göndərir
        // (spesifikasiyanın əsas məqsədi budur). Əvvəlki sıra (`$_GET` ilk) bu başlığı HƏMİŞƏ
        // e'tibarsız edirdi — nəticədə uzun açıq qalan tab köhnə, donmuş nöqtədən sorğulamağa
        // davam edirdi və yeni elanlar YALNIZ tam səhifə yenilənəndə (tab dəyişəndə) görünürdü.
        // app.birlikde.biz-in `SseController::lovhe()`-i YALNIZ başlığı oxuyur — eyni səbəbdən.
        $lastId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? ($_GET['lastId'] ?? 0));

        // KRİTİK: session faylı lock-u burada buraxılmalıdır. PHP-nin fayl-əsaslı sessiya
        // handler-i session_start()-dan session_write_close()-a qədər EXCLUSIVE lock saxlayır —
        // bağlamasaq, bu uzun (≤55s) SSE loop-u eyni brauzerdən gələn BÜTÜN digər sorğuları
        // (məs. kart fraqmenti fetch-i) session bağlanana qədər bloklayardı.
        session_write_close();

        Sse::stream(['feed', 'driver_' . $driverId], $lastId);
    }

    public function customer(): void
    {
        Auth::requireRole('customer', '/giris');
        $customerId = (int) Auth::id();
        // Bax feed()-dəki şərh — Last-Event-ID başlığı GET parametrindən ÖNCƏ oxunmalıdır.
        $lastId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? ($_GET['lastId'] ?? 0));

        session_write_close(); // bax yuxarıdakı şərh (feed())

        Sse::stream(['customer_' . $customerId], $lastId);
    }
}
