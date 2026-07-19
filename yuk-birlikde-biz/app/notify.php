<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/webpush.php';

const NOTIFY_CATEGORY_COLUMNS = [
    'offers' => 'notify_offers',
    'status' => 'notify_status',
    'system' => 'notify_system',
];

// Bildiriş qeydi yaradır (Hissə 4.11 "Bildirişlər" ekranının məlumat mənbəyi)
// və eyni hadisə üçün Web Push göndərir (Hissə 7.4) — bu iki kanal eyni
// business hadisələrə bağlıdır, ona görə vahid çağırış nöqtəsində birləşdirilib.
// $category istifadəçinin Profil > Bildiriş ayarları toggle-larından hansına uyğun gəldiyini müəyyən edir.
function notify_user(int $userId, string $type, string $title, string $body, ?string $link = null, string $category = 'status'): void
{
    if (!settings_get_bool('notifications_enabled', true)) {
        return;
    }

    $column = NOTIFY_CATEGORY_COLUMNS[$category] ?? 'notify_status';
    $stmt = db()->prepare("SELECT {$column} FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    if ($stmt->fetchColumn() == 0) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, type, title, body, link) VALUES (:user_id, :type, :title, :body, :link)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'link' => $link,
    ]);

    push_to_user($userId, $title, $body, $link);
}

// push_subscriptions-dakı bütün cihazlara push göndərir; ölü (404/410) abunəliklər silinir.
function push_to_user(int $userId, string $title, string $body, ?string $link = null): void
{
    $db = db();
    $stmt = $db->prepare('SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);

    $payload = json_encode(['title' => $title, 'body' => $body, 'link' => $link], JSON_UNESCAPED_UNICODE);

    foreach ($stmt->fetchAll() as $subscription) {
        try {
            send_web_push($subscription, $payload, static function () use ($db, $subscription): void {
                $db->prepare('DELETE FROM push_subscriptions WHERE id = :id')->execute(['id' => $subscription['id']]);
            });
        } catch (Throwable $e) {
            // Push çatdırılması uğursuz olsa da in-app bildiriş artıq yazılıb — səssizcə keç.
        }
    }
}
