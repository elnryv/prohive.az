<?php
declare(strict_types=1);

// Hər 5 dəqiqədə işləyir (crontab). Müddəti bitmiş aktiv/gözləyən elanları
// bağlayır və sahibinə bildiriş yazır — bax Hissə 4.4.

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../notify.php';
require_once __DIR__ . '/../texts.php';
require_once __DIR__ . '/../sse_publish.php';

$db = db();

$stmt = $db->prepare(
    "SELECT id, customer_id FROM orders WHERE status IN ('active', 'waiting') AND expires_at <= NOW()"
);
$stmt->execute();
$expired = $stmt->fetchAll();

foreach ($expired as $order) {
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE orders SET status = "expired" WHERE id = :id')->execute(['id' => $order['id']]);
        $db->prepare('UPDATE offers SET status = "archived" WHERE order_id = :id AND status = "pending"')
            ->execute(['id' => $order['id']]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        continue;
    }

    notify_user(
        (int) $order['customer_id'],
        'order_expired',
        'Elanın müddəti bitdi',
        text('notify_order_expired'),
        "/elan/{$order['id']}"
    );

    publish_event('feed', 'listing.expired', ['id' => (int) $order['id']]);
}

echo count($expired) . " elan müddəti bitdi olaraq işarələndi.\n";
