<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-Sent Events: sse_events cədvəlini poll edən minimal native mexanizm
 * (bölmə 11.5). Composer/kitabxana yoxdur — uzun-polling loop + ping.
 *
 * Kanallar: feed (bütün sürücü lentləri), driver_{id}, customer_{id}, admin.
 */
final class Sse
{
    // FAZA 23: app.birlikde.biz-in `SseController::lovhe()`-i ilə EYNİ ədədlər
    // (MAX_ITERATIONS=1800, SLEEP_SECONDS=2 → 3600s/1 saat dövr). Əvvəlki 55s dövr
    // hər bağlantını saatda ~65 dəfə məcburi yenidən-qoşulmağa vadar edirdi —
    // reconnect yolundakı istənilən qırığa (brauzer/mobil şəbəkə) məruz qalma
    // ehtimalını 65 dəfə artırırdı. `fastcgi_read_timeout` (nginx) İKİ ardıcıl
    // oxuma ARASINDAKI boşluğa aiddir, ÜMUMİ bağlantı müddətinə YOX — bizim 2s-lik
    // ping-lər davam etdiyi üçün bu dəyişiklik nginx konfiqini TƏLƏB ETMİR.
    private const MAX_ITERATIONS = 1800;
    private const SLEEP_SECONDS = 2;

    public static function publish(string $channel, string $event, array $payload = []): void
    {
        $stmt = DB::conn()->prepare('INSERT INTO sse_events (channel, event, payload) VALUES (?, ?, ?)');
        $stmt->execute([$channel, $event, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * @param string[] $channels
     */
    public static function stream(array $channels, int $lastEventId = 0): void
    {
        // FAZA 19: `nginx buffering off` TƏK BAŞINA kifayət etmədi — PHP-nin ÖZÜ
        // (php-fpm pool php.ini-də) `zlib.output_compression` aktiv ola bilər, bu
        // halda flush()/ob_flush() çağırışları heç nəyi dəyişmir, çünki bayt-lar
        // artıq PHP-nin daxili gzip buferindən keçir və yalnız bufer dolanda və ya
        // skript bitəndə (bizim halda dövrün sonunda) çıxır — bu da eynilə
        // "yalnız tab dəyişəndə görünür" simptomunu verir, nginx-dən ASILI OLMADAN.
        // `max_execution_time` (bəzi php.ini-lərdə default 30s) də dövrü vaxtından
        // əvvəl kəsə bilər — set_time_limit(0) ilə bu SSE skripti üçün xüsusi ləğv edilir.
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', 'off');
        @ini_set('implicit_flush', '1');
        set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $lastId = $lastEventId;

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            if (connection_aborted()) {
                return;
            }

            $placeholders = implode(',', array_fill(0, count($channels), '?'));
            $stmt = DB::conn()->prepare(
                "SELECT id, channel, event, payload FROM sse_events
                 WHERE channel IN ({$placeholders}) AND id > ? ORDER BY id ASC LIMIT 100"
            );
            $stmt->execute([...$channels, $lastId]);
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $data = [
                    'channel' => $row['channel'],
                    'payload' => json_decode((string) $row['payload'], true),
                ];
                echo "id: {$lastId}\n";
                echo "event: {$row['event']}\n";
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            }

            if ($rows !== []) {
                @ob_flush();
                @flush();
            }

            // Nginx `fastcgi_read_timeout` (65s, bax nginx/*.conf) İKİ ardıcıl oxuma
            // ARASINDAKI boşluğa aiddir — bu 2s-lik keep-alive şərh sətri o boşluğu
            // heç vaxt 65s-ə çatdırmır, ona görə MAX_ITERATIONS-ı 3600s-ə qaldırmaq
            // nginx konfiqini dəyişməyi TƏLƏB ETMİR.
            echo ": ping\n\n";
            @ob_flush();
            @flush();

            sleep(self::SLEEP_SECONDS);
        }

        echo "event: ping\ndata: {}\n\n";
        @ob_flush();
        @flush();
    }
}
