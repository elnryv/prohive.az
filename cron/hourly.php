<?php

declare(strict_types=1);

/**
 * Saatlıq cron (bölmə 12.2):
 *  - expires_at keçən active elan → expired (+ feed-dən silmə siqnalı)
 *  - accepted elan, tamamlanma vaxtı keçib (move_date+1 gün, ya da accepted_at+72 saat) → completed,
 *    sürücünün jobs_done+1 (bölmə 6.7)
 *  - köhnə OG şəkilləri təmizlənir (silinmiş/vaxtı keçmiş elanlar üçün)
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Config;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\Sse;

$pdo = DB::conn();

// 1) active -> expired
$expiredStmt = $pdo->prepare("SELECT id FROM listings WHERE status = 'active' AND expires_at < NOW()");
$expiredStmt->execute();
$expiredIds = array_column($expiredStmt->fetchAll(), 'id');

if ($expiredIds !== []) {
    $placeholders = implode(',', array_fill(0, count($expiredIds), '?'));
    $pdo->prepare("UPDATE listings SET status = 'expired' WHERE id IN ({$placeholders})")->execute($expiredIds);

    foreach ($expiredIds as $id) {
        $ev = $pdo->prepare('INSERT INTO listing_events (listing_id, event) VALUES (?, "expired")');
        $ev->execute([$id]);
        Sse::publish('feed', 'listing_closed', ['listing_id' => (int) $id]);
    }
    echo count($expiredIds) . " elan expired edildi.\n";
}

// 2) accepted -> completed
$acceptedStmt = $pdo->query("SELECT id, move_date, accepted_at, accepted_offer_id FROM listings WHERE status = 'accepted'");
$completedCount = 0;
foreach ($acceptedStmt->fetchAll() as $listing) {
    $dueAt = ListingRules::completionDueAt($listing['move_date'], $listing['accepted_at']);
    if (strtotime($dueAt) > time()) {
        continue;
    }

    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare("UPDATE listings SET status = 'completed', completed_at = NOW() WHERE id = ? AND status = 'accepted'");
        $upd->execute([$listing['id']]);

        if ($upd->rowCount() === 1) {
            $driverStmt = $pdo->prepare('SELECT driver_id FROM offers WHERE id = ?');
            $driverStmt->execute([$listing['accepted_offer_id']]);
            $driverId = $driverStmt->fetch()['driver_id'] ?? null;
            if ($driverId !== null) {
                $pdo->prepare('UPDATE users SET jobs_done = jobs_done + 1 WHERE id = ?')->execute([$driverId]);
            }
            $ev = $pdo->prepare('INSERT INTO listing_events (listing_id, event) VALUES (?, "completed")');
            $ev->execute([$listing['id']]);
            $completedCount++;
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
echo $completedCount . " iş completed edildi.\n";

// 3) köhnə OG şəkilləri təmizliyi: active/accepted olmayan elanların 30 gündən köhnə OG faylları
$ogDir = (string) Config::get('upload.og_image_dir');
$oldStmt = $pdo->prepare(
    "SELECT public_code FROM listings WHERE status IN ('expired','removed','completed') AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$oldStmt->execute();
$cleaned = 0;
foreach ($oldStmt->fetchAll() as $row) {
    $path = $ogDir . '/' . $row['public_code'] . '.png';
    if (is_file($path)) {
        unlink($path);
        $cleaned++;
    }
}
echo $cleaned . " köhnə OG şəkli silindi.\n";
