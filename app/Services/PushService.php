<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Logger;
use App\Models\PushAbune;
use Minishlink\WebPush\ContentEncoding;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Web Push göndərici — RFC 8291 (şifrələnmə) + RFC 8292 (VAPID) minishlink/
 * web-push kitabxanası ilə (bax composer.json). VAPID açarları
 * `database/generate_vapid_keys.php` ilə bir dəfə yaradılır, .env-də saxlanır.
 */
final class PushService
{
    private PushAbune $pushAbuneler;
    private ?WebPush $webPush = null;
    private bool $webPushHazirlanib = false;

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

        $webPush = $this->webPushClient();
        if ($webPush === null) {
            Logger::warning('push_vapid_konfiqurasiya_yoxdur', ['user_id' => $userId]);
            return;
        }

        $payload = json_encode([
            'title' => $baslik,
            'body' => $metn,
            'url' => $url ?? '/',
        ], JSON_UNESCAPED_UNICODE);

        foreach ($abunelikler as $abunelik) {
            $subscription = new Subscription(
                $abunelik['endpoint'],
                $abunelik['p256dh'],
                $abunelik['auth'],
                ContentEncoding::aes128gcm
            );

            try {
                $report = $webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    continue;
                }

                Logger::warning('push_gonderilmedi', [
                    'user_id' => $userId,
                    'endpoint' => $abunelik['endpoint'],
                    'sebeb' => $report->getReason(),
                ]);

                // 404/410 — brauzer abunəlikdən çıxıb, artıq mövcud deyil.
                if ($report->isSubscriptionExpired()) {
                    $this->pushAbuneler->deleteByEndpoint($abunelik['endpoint']);
                }
            } catch (\Throwable $e) {
                Logger::error('push_gonderme_xetasi', [
                    'user_id' => $userId,
                    'endpoint' => $abunelik['endpoint'],
                    'xeta' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * WebPush müştərisi TƏNBƏL yaradılır — SifarisService kimi sinif hər
     * sorğuda PushService-i konstruktora salır, amma əksər sorğular heç vaxt
     * `gonder()` çağırmır; VAPID/mühit yoxlamasını yalnız faktiki göndəriş
     * anında etmək lazımsız iş və (GMP/BCMath yoxdursa) kitabxananın atdığı
     * performans xəbərdarlığının hər sorğuda işə düşməsinin qarşısını alır.
     *
     * `@` GMP/BCMath yoxdursa kitabxananın verdiyi məlumatlandırıcı
     * `trigger_error()`-u susdurur (funksional deyil, sadəcə "daha sürətli
     * olardı" xəbərdarlığıdır) — `App\Core\ErrorHandler` APP_DEBUG=true
     * olduqda HƏR notice-i istisnaya çevirdiyi üçün bu olmasa sorğu 500 ilə
     * çökür. Production-da `php-bcmath` genişlənməsini quraşdırmaq bu
     * xəbərdarlığı kökündən aradan qaldırır (tövsiyə olunur, tələb olunmur).
     */
    private function webPushClient(): ?WebPush
    {
        if ($this->webPushHazirlanib) {
            return $this->webPush;
        }

        $this->webPushHazirlanib = true;

        $publicKey = (string) Env::get('VAPID_PUBLIC_KEY', '');
        $privateKey = (string) Env::get('VAPID_PRIVATE_KEY', '');
        $subject = (string) Env::get('VAPID_SUBJECT', '');

        if ($publicKey === '' || $privateKey === '' || $subject === '') {
            return null;
        }

        $this->webPush = @new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        return $this->webPush;
    }
}
