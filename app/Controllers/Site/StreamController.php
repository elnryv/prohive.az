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
        $lastId = (int) ($_GET['lastId'] ?? ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0));

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
        $lastId = (int) ($_GET['lastId'] ?? ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0));

        session_write_close(); // bax yuxarıdakı şərh (feed())

        Sse::stream(['customer_' . $customerId], $lastId);
    }
}
