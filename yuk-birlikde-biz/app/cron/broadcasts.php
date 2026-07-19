<?php
declare(strict_types=1);

// Hər dəqiqə işləyir (crontab). Planlaşdırılmış broadcast-ları vaxtı çatanda göndərir.

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../broadcast.php';

$db = db();
$stmt = $db->query("SELECT id FROM broadcast_log WHERE sent_at IS NULL AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    send_broadcast((int) $id);
}

echo count($ids) . " planlaşdırılmış bildiriş göndərildi.\n";
