<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Models\PushAbune;

/**
 * Web Push tetikleyici nöqtəsi — bax CLAUDE.md bölmə 9.3.
 *
 * QEYD (əhatə qərarı): faktiki RFC 8291 şifrələnmiş göndərmə (aes128gcm + VAPID
 * JWT) BURADA YOXDUR — istifadəçi ilə razılaşdırılıb ki, bu fazada yalnız
 * infrastruktur (abunəlik saxlama, VAPID açar idarəsi, hadisə trigger nöqtələri)
 * hazırlanır. gonder() hazırda push_abuneler-i tapır və NİYYƏTİ storage/logs-a
 * yazır — real şəbəkə çağırışı etmir. Real göndərmə əlavə ediləndə yalnız bu
 * metodun daxili hissəsi dəyişdirilməlidir, çağıran tərəf (trigger nöqtələri)
 * dəyişmədən qalır.
 */
final class PushService
{
    private PushAbune $pushAbuneler;

    public function __construct()
    {
        $this->pushAbuneler = new PushAbune();
    }

    public function gonder(int $userId, string $baslik, string $metn, ?string $url = null): void
    {
        $abunelikler = $this->pushAbuneler->findByUserId($userId);

        if ($abunelikler === []) {
            return;
        }

        foreach ($abunelikler as $abunelik) {
            Logger::info('push_gonderilecekdi', [
                'user_id' => $userId,
                'endpoint' => $abunelik['endpoint'],
                'baslik' => $baslik,
                'metn' => $metn,
                'url' => $url,
            ]);
        }
    }
}
