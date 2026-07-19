<?php
declare(strict_types=1);

// Gündə 1 dəfə işləyir (crontab). Abunə bitmə xəbərdarlıqları və bitmə
// keçidi — bax Hissə 5.7 "Abunə bitmə davranışı".

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../notify.php';
require_once __DIR__ . '/../texts.php';
require_once __DIR__ . '/../sse_publish.php';

$db = db();
$sentCount = 0;

// 3 gün və 1 gün əvvəl xəbərdarlıq
$upcoming = [
    ['days' => 3, 'column' => 'notice_3d_sent'],
    ['days' => 1, 'column' => 'notice_1d_sent'],
];

foreach ($upcoming as $tier) {
    $stmt = $db->prepare(
        "SELECT id, driver_id, ends_at FROM subscriptions
         WHERE status = 'active' AND {$tier['column']} = 0
           AND ends_at <= DATE_ADD(NOW(), INTERVAL :days DAY)"
    );
    $stmt->execute(['days' => $tier['days']]);

    foreach ($stmt->fetchAll() as $sub) {
        $dateLabel = date('d.m.Y', strtotime($sub['ends_at']));
        notify_user(
            (int) $sub['driver_id'],
            'subscription_expiring',
            'Abunə bitmək üzrədir',
            "Abunəniz bitmək üzrədir — {$dateLabel}.",
            '/abune',
            'system'
        );
        $db->prepare("UPDATE subscriptions SET {$tier['column']} = 1 WHERE id = :id")
            ->execute(['id' => $sub['id']]);
        $sentCount++;
    }
}

// Bitmiş abunələr: status → expired + push. Bundan sonra artıq təklif göndərmə
// kilidlənir (subscription_is_active() ends_at > NOW() şərtini artıq keçmir),
// sürücü lentə baxmağa/filtrə/profilə davam edə bilir (Hissə 5.7 FOMO qaydası).
$stmt = $db->query("SELECT id, driver_id FROM subscriptions WHERE status = 'active' AND ends_at <= NOW()");
foreach ($stmt->fetchAll() as $sub) {
    $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = :id")->execute(['id' => $sub['id']]);
    notify_user(
        (int) $sub['driver_id'],
        'subscription_expired',
        'Abunə bitdi',
        'Abunəniz bitdi. Təklif göndərmək üçün yenidən abunə olun.',
        '/abune',
        'system'
    );
    publish_event("user:{$sub['driver_id']}", 'subscription.expired', []);
    $sentCount++;
}

echo "{$sentCount} abunə bildirişi göndərildi.\n";
