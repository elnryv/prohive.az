<?php
declare(strict_types=1);

// GET /sse/stream.php?channels=feed,user:42 — bax Hissə 7.3.
// Sessiya yoxlanır, istifadəçi yalnız öz kanallarına abunə ola bilər.

require_once __DIR__ . '/../../app/response.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/settings.php';
require_once __DIR__ . '/../../app/auth.php';

$user = session_user();
if ($user === null) {
    http_response_code(401);
    exit;
}

function authorized_channels(array $requested, array $user): array
{
    $db = db();
    $allowed = [];

    foreach ($requested as $channel) {
        if ($channel === 'system') {
            $allowed[] = $channel;
        } elseif ($channel === 'feed' && $user['role'] === 'driver') {
            $allowed[] = $channel;
        } elseif (preg_match('/^user:(\d+)$/', $channel, $m) && (int) $m[1] === (int) $user['id']) {
            $allowed[] = $channel;
        } elseif (preg_match('/^listing:(\d+)$/', $channel, $m)) {
            $stmt = $db->prepare('SELECT id FROM orders WHERE id = :id AND customer_id = :customer_id');
            $stmt->execute(['id' => (int) $m[1], 'customer_id' => $user['id']]);
            if ($stmt->fetch() !== false) {
                $allowed[] = $channel;
            }
        }
    }

    return $allowed;
}

$requested = array_filter(explode(',', (string) ($_GET['channels'] ?? '')));
$channels = authorized_channels($requested, $user);

if ($channels === []) {
    http_response_code(403);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

while (ob_get_level() > 0) {
    ob_end_flush();
}

set_time_limit(0);
ignore_user_abort(true);

$db = db();
$placeholders = implode(',', array_fill(0, count($channels), '?'));

// Təzə bağlantıda (Last-Event-ID yoxdursa) yalnız bundan sonrakı hadisələr
// göndərilir; reconnect zamanı brauzer Last-Event-ID-ni avtomatik göndərir
// və buraxılmış hadisələr "catch-up" ilə çatdırılır.
$lastEventId = $_SERVER['HTTP_LAST_EVENT_ID'] ?? null;
if ($lastEventId === null) {
    $stmt = $db->query('SELECT COALESCE(MAX(id), 0) FROM events');
    $lastEventId = (int) $stmt->fetchColumn();
} else {
    $lastEventId = (int) $lastEventId;
}

$startTime = time();
$lastHeartbeat = time();
$maxDuration = 3600; // Nginx proxy_read_timeout ilə uyğun (Hissə 7.3)

while (true) {
    if (connection_aborted()) {
        break;
    }

    $stmt = $db->prepare(
        "SELECT id, type, payload FROM events WHERE id > ? AND channel IN ({$placeholders}) ORDER BY id ASC LIMIT 100"
    );
    $stmt->execute([$lastEventId, ...$channels]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        echo "id: {$row['id']}\n";
        echo "event: {$row['type']}\n";
        echo "data: {$row['payload']}\n\n";
        $lastEventId = (int) $row['id'];
    }

    if ($rows !== []) {
        flush();
    }

    if (time() - $lastHeartbeat >= 25) {
        echo ": ping\n\n";
        flush();
        $lastHeartbeat = time();
    }

    if (time() - $startTime >= $maxDuration) {
        break;
    }

    usleep(500000);
}
