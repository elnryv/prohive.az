<?php
declare(strict_types=1);

/**
 * Gecə cron-u (bölmə 12.4, hər gecə 03:00 işə düşməlidir).
 * İşə salma: php cron/daily.php  (və ya crontab-da, bax deploy/crontab.example)
 */

require_once __DIR__ . '/../app/bootstrap.php';

$summary = [];

// 1. trial_until keçmiş 'trial' sahiblər → 'expired' (evlər avtomatik gizlənir)
$stmt = DB::query("UPDATE owners SET billing_status = 'expired' WHERE billing_status = 'trial' AND trial_until < CURDATE()");
$summary['trial_to_expired'] = $stmt->rowCount();

// 2. paid_until keçmiş 'paid' sahiblər → 'expired'
$stmt = DB::query("UPDATE owners SET billing_status = 'expired' WHERE billing_status = 'paid' AND paid_until < CURDATE()");
$summary['paid_to_expired'] = $stmt->rowCount();

// 3. Bitməyə 5 gün qalanlar — admin dashboard-da canlı hesablanır (AdminRepository::expiringOwners),
//    ayrıca cədvəl/fayl saxlanmasına ehtiyac yoxdur; sayı loga yazılır.
$summary['expiring_soon'] = count(AdminRepository::expiringOwners(5));

// 4a. house_calendar keçmiş tarixlər silinir
$stmt = DB::query('DELETE FROM house_calendar WHERE busy_date < CURDATE()');
$summary['calendar_cleaned'] = $stmt->rowCount();

// 4b. sse_events 7 gündən köhnə sətirlər silinir (Faza 5 hazırlığı)
$stmt = DB::query('DELETE FROM sse_events WHERE created_at < NOW() - INTERVAL 7 DAY');
$summary['sse_events_cleaned'] = $stmt->rowCount();

// 5. sitemap.xml yenilənir (əsas versiya — tam SEO cilası Faza 6-nın işidir)
$summary['sitemap_urls'] = regenerateSitemap();

// storage/tmp təmizlənir (köhnə dedupe/rate-limit marker faylları, 1 gündən köhnə)
$summary['tmp_files_cleaned'] = cleanTmpDir(APP_ROOT . '/storage/tmp', 86400);

echo '[' . date('c') . "] daily.php tamamlandı: " . json_encode($summary, JSON_UNESCAPED_UNICODE) . PHP_EOL;

function regenerateSitemap(): int
{
    $urls = [
        ['loc' => SITE_BASE_URL . '/', 'priority' => '1.0'],
        ['loc' => SITE_BASE_URL . '/axtar', 'priority' => '0.6'],
        ['loc' => SITE_BASE_URL . '/haqqinda', 'priority' => '0.3'],
        ['loc' => SITE_BASE_URL . '/sertler', 'priority' => '0.3'],
        ['loc' => SITE_BASE_URL . '/mexfilik', 'priority' => '0.3'],
        ['loc' => SITE_BASE_URL . '/ev-sahibi-ol', 'priority' => '0.5'],
    ];

    foreach (DB::all('SELECT slug FROM regions WHERE is_active = 1') as $r) {
        $urls[] = ['loc' => SITE_BASE_URL . '/bolge/' . $r['slug'], 'priority' => '0.7'];
    }

    $houseSql = "SELECT h.slug, h.updated_at FROM houses h
                 JOIN owners o ON o.id = h.owner_id
                 WHERE h.status = 'approved'
                   AND (
                       (o.billing_status = 'trial' AND o.trial_until >= CURDATE())
                       OR (o.billing_status = 'paid' AND o.paid_until >= CURDATE())
                       OR (o.billing_status = 'free')
                   )";
    foreach (DB::all($houseSql) as $h) {
        $urls[] = [
            'loc' => SITE_BASE_URL . '/ev/' . $h['slug'],
            'priority' => '0.8',
            'lastmod' => date('Y-m-d', strtotime((string) $h['updated_at'])),
        ];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
        if (isset($u['lastmod'])) {
            $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
        }
        $xml .= '<priority>' . $u['priority'] . '</priority></url>' . "\n";
    }
    $xml .= '</urlset>' . "\n";

    file_put_contents(APP_ROOT . '/public/sitemap.xml', $xml);

    return count($urls);
}

function cleanTmpDir(string $dir, int $maxAgeSeconds): int
{
    if (!is_dir($dir)) {
        return 0;
    }
    $count = 0;
    $now = time();
    foreach (glob($dir . '/*.marker') ?: [] as $file) {
        if ($now - (filemtime($file) ?: 0) > $maxAgeSeconds) {
            @unlink($file);
            $count++;
        }
    }
    foreach (glob($dir . '/ratelimit/*.json') ?: [] as $file) {
        if ($now - (filemtime($file) ?: 0) > $maxAgeSeconds) {
            @unlink($file);
            $count++;
        }
    }
    return $count;
}
