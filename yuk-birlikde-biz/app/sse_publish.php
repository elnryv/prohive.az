<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Hər hadisə events cədvəlinə yazılır; stream.php son göndərilən id-dən
// sonrakıları oxuyub SSE ilə push edir — bax Hissə 7.3.
function publish_event(string $channel, string $type, array $payload): void
{
    db()->prepare('INSERT INTO events (channel, type, payload) VALUES (:channel, :type, :payload)')
        ->execute([
            'channel' => $channel,
            'type' => $type,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
}
