<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

const NOTIFY_CATEGORY_COLUMNS = [
    'offers' => 'notify_offers',
    'status' => 'notify_status',
    'system' => 'notify_system',
];

// Bildiriş qeydi yaradır (Hissə 4.11 "Bildirişlər" ekranının məlumat mənbəyi).
// Real-time çatdırılma (SSE/Push) Faza 3/4-də bu cədvəlin üzərinə qurulur.
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
}
