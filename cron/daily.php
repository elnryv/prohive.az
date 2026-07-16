<?php

declare(strict_types=1);

/**
 * Günlük cron (bölmə 12.2): trial/paid bitmə → expired, bitməyə 5 gün qalanlara
 * xatırlatma push-u, sse_events təmizliyi, sitemap yenilənməsi.
 */

require __DIR__ . '/bootstrap.php';

use App\Core\DB;
use App\Core\Settings;
use App\Core\WebPush;

$pdo = DB::conn();
$paymentsEnabled = Settings::get('payments_enabled', '1') === '1';

if ($paymentsEnabled) {
    // trial/paid müddəti keçmiş sürücülər → expired (Q-Y7: açar sönülü ikən bu addım
    // heç bir təsir etmir, çünki Auth::isActiveDriver() onsuz da hamını aktiv sayır).
    $expireStmt = $pdo->prepare(
        "UPDATE users SET billing_status = 'expired'
         WHERE role = 'driver' AND (
            (billing_status = 'trial' AND trial_until < CURDATE())
            OR (billing_status = 'paid' AND paid_until < CURDATE())
         )"
    );
    $expireStmt->execute();
    echo $expireStmt->rowCount() . " sürücünün abunəsi expired edildi.\n";

    // 5 gün sonra bitəcək olanlara xatırlatma
    $reminderStmt = $pdo->query(
        "SELECT id FROM users WHERE role = 'driver' AND (
            (billing_status = 'trial' AND trial_until = DATE_ADD(CURDATE(), INTERVAL 5 DAY))
            OR (billing_status = 'paid' AND paid_until = DATE_ADD(CURDATE(), INTERVAL 5 DAY))
         )"
    );
    $reminderIds = array_map('intval', array_column($reminderStmt->fetchAll(), 'id'));
    if ($reminderIds !== []) {
        WebPush::sendToUsersLocalized($reminderIds, static fn () => [
            'title' => t('push.billing_reminder_title'),
            'body' => t('billing.reminder_body'),
            'url' => '/surucu/odenis',
        ]);
        echo count($reminderIds) . " sürücüyə ödəniş xatırlatması göndərildi.\n";
    }
}

// sse_events 7 gündən köhnə sətirlər təmizlənir
$cleaned = $pdo->exec("DELETE FROM sse_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
echo $cleaned . " köhnə sse_events sətri silindi.\n";

// Sitemap (bölmə 12.3): aktiv elanlar + statik səhifələr
$baseUrl = rtrim((string) \App\Core\Config::get('app.base_url'), '/');
$listingsStmt = $pdo->query("SELECT public_code, created_at FROM listings WHERE status = 'active'");
$urls = ["{$baseUrl}/"];
foreach ($listingsStmt->fetchAll() as $row) {
    $urls[] = "{$baseUrl}/e/{$row['public_code']}";
}
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1) . "</loc></url>\n";
}
$xml .= '</urlset>' . "\n";
file_put_contents(dirname(__DIR__) . '/public/sitemap.xml', $xml);
echo "sitemap.xml yeniləndi (" . count($urls) . " URL).\n";
