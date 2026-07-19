<?php
declare(strict_types=1);

// Hər dəqiqə işləyir (crontab). Danışıq gedən sifarişlər üçün müştəriyə
// xatırlatma göndərir — bax Hissə 4.8. İntervallar settings-dən oxunur.

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../notify.php';
require_once __DIR__ . '/../texts.php';
require_once __DIR__ . '/../sse_publish.php';

$intervals = array_values(array_filter(array_map('intval', explode(',', settings_get('reminder_intervals', '15,30,60,1440')))));
sort($intervals);

if ($intervals === []) {
    exit;
}

$db = db();
$stmt = $db->query("SELECT id, customer_id, updated_at, reminder_tier_sent FROM orders WHERE status = 'negotiating'");
$orders = $stmt->fetchAll();

$sentCount = 0;

foreach ($orders as $order) {
    $elapsedMinutes = (time() - strtotime($order['updated_at'])) / 60;
    $tier = (int) $order['reminder_tier_sent'];

    while ($tier < count($intervals) && $elapsedMinutes >= $intervals[$tier]) {
        $tier++;

        notify_user(
            (int) $order['customer_id'],
            'reminder',
            'Xatırlatma',
            text('notify_reminder'),
            "/elan/{$order['id']}"
        );
        publish_event("user:{$order['customer_id']}", 'reminder', ['order_id' => (int) $order['id']]);
        $sentCount++;
    }

    if ($tier !== (int) $order['reminder_tier_sent']) {
        $db->prepare('UPDATE orders SET reminder_tier_sent = :tier WHERE id = :id')
            ->execute(['tier' => $tier, 'id' => $order['id']]);
    }
}

echo "{$sentCount} xatırlatma göndərildi.\n";
