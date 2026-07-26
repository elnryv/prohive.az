<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notify.php';
require_once __DIR__ . '/sse_publish.php';

// Hissə 10.10 — auditoriyaya uyğun istifadəçi id-lərini qaytarır (yalnız aktiv hesablar).
function broadcast_audience_user_ids(string $audience): array
{
    $db = db();

    return match ($audience) {
        'all' => $db->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN),
        'customers' => $db->query("SELECT id FROM users WHERE status = 'active' AND role = 'customer'")->fetchAll(PDO::FETCH_COLUMN),
        'drivers' => $db->query("SELECT id FROM users WHERE status = 'active' AND role = 'driver'")->fetchAll(PDO::FETCH_COLUMN),
        'active_subs' => $db->query(
            "SELECT DISTINCT u.id FROM users u JOIN subscriptions s ON s.driver_id = u.id
             WHERE u.status = 'active' AND s.status = 'active' AND s.ends_at > NOW()"
        )->fetchAll(PDO::FETCH_COLUMN),
        'expired_subs' => $db->query(
            "SELECT DISTINCT u.id FROM users u JOIN subscriptions s ON s.driver_id = u.id
             WHERE u.status = 'active' AND s.status = 'expired'
               AND u.id NOT IN (SELECT driver_id FROM subscriptions WHERE status = 'active' AND ends_at > NOW())"
        )->fetchAll(PDO::FETCH_COLUMN),
        default => [],
    };
}

function send_broadcast(int $broadcastId): void
{
    $db = db();
    $stmt = $db->prepare('SELECT * FROM broadcast_log WHERE id = :id');
    $stmt->execute(['id' => $broadcastId]);
    $broadcast = $stmt->fetch();

    if ($broadcast === false || $broadcast['sent_at'] !== null) {
        return;
    }

    $userIds = broadcast_audience_user_ids($broadcast['audience']);
    $successCount = 0;

    foreach ($userIds as $userId) {
        try {
            notify_user((int) $userId, 'broadcast', $broadcast['title'], $broadcast['body'], null, 'system');
            publish_event("user:{$userId}", 'admin.broadcast', ['title' => $broadcast['title'], 'body' => $broadcast['body']]);
            $successCount++;
        } catch (Throwable $e) {
            // sayğac üçün uğursuz say artmır, davam edir
        }
    }

    $db->prepare('UPDATE broadcast_log SET sent_at = NOW(), success_count = :s, fail_count = :f WHERE id = :id')
        ->execute(['s' => $successCount, 'f' => count($userIds) - $successCount, 'id' => $broadcastId]);
}
