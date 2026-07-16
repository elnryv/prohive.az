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
    public static function publish(string $channel, string $event, array $payload = []): void
    {
        $stmt = DB::conn()->prepare('INSERT INTO sse_events (channel, event, payload) VALUES (?, ?, ?)');
        $stmt->execute([$channel, $event, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * @param string[] $channels
     */
    public static function stream(array $channels, int $lastEventId = 0, int $maxSeconds = 55): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $start = time();
        $lastId = $lastEventId;

        while (true) {
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

            if (time() - $start >= $maxSeconds) {
                echo "event: ping\ndata: {}\n\n";
                @ob_flush();
                @flush();
                return;
            }

            echo ": ping\n\n";
            @ob_flush();
            @flush();

            sleep(2);
        }
    }
}
