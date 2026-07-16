<?php
declare(strict_types=1);

/**
 * Real-time hadisə yayımı (bölmə 11.4): sse_events cədvəlindən poll ilə
 * oxuyub axıdır. Ayrıca broker/Redis tələb olunmur — v1 miqyasında DB poll
 * kifayətdir.
 */
final class Sse
{
    private const POLL_INTERVAL_SECONDS = 2;
    private const PING_INTERVAL_SECONDS = 25;
    private const MAX_DURATION_SECONDS = 300; // 5 dəq — client avtomatik reconnect edir

    /** @param array<string,mixed> $payload */
    public static function emit(string $channel, string $eventType, array $payload): void
    {
        DB::query(
            'INSERT INTO sse_events (channel, event_type, payload, created_at) VALUES (:channel, :type, :payload, NOW())',
            [
                ':channel' => $channel,
                ':type' => $eventType,
                ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    public static function stream(string $channel): void
    {
        set_time_limit(0);
        ignore_user_abort(false);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // nginx: fastcgi_buffering off (bölmə 3, 11.4) əvəzi

        $lastId = self::resolveLastEventId($channel);
        $started = time();
        $lastPing = time();

        while (true) {
            if (connection_aborted()) {
                break;
            }
            if (time() - $started > self::MAX_DURATION_SECONDS) {
                break;
            }

            $rows = DB::all(
                'SELECT id, event_type, payload FROM sse_events WHERE channel = :c AND id > :id ORDER BY id ASC LIMIT 50',
                [':c' => $channel, ':id' => $lastId]
            );

            foreach ($rows as $row) {
                echo 'id: ' . $row['id'] . "\n";
                echo 'event: ' . $row['event_type'] . "\n";
                echo 'data: ' . $row['payload'] . "\n\n";
                $lastId = (int) $row['id'];
            }

            if ($rows !== []) {
                self::flush();
                $lastPing = time();
            } elseif (time() - $lastPing >= self::PING_INTERVAL_SECONDS) {
                echo ": ping\n\n";
                self::flush();
                $lastPing = time();
            }

            sleep(self::POLL_INTERVAL_SECONDS);
        }
    }

    private static function resolveLastEventId(string $channel): int
    {
        $header = $_SERVER['HTTP_LAST_EVENT_ID'] ?? ($_GET['lastEventId'] ?? null);
        if ($header !== null && ctype_digit((string) $header)) {
            return (int) $header;
        }
        // İlk qoşulmada tarixçəni "burst" etməmək üçün cari son id-dən başlanılır.
        $row = DB::one('SELECT MAX(id) AS id FROM sse_events WHERE channel = :c', [':c' => $channel]);
        return (int) ($row['id'] ?? 0);
    }

    private static function flush(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        @flush();
    }
}
