<?php

declare(strict_types=1);

/**
 * CLI: php cron/abunelik_yoxla.php (gündəlik işə salınmalıdır — bax deploy/VPS_QURULUM.md).
 * Bax CLAUDE.md bölmə 9.1.2: bitənləri bağlayır (aktiv=0), bitməyə yaxın olanlara
 * (3 gün) xəbərdarlıq hadisəsi yazır. Faktiki Web Push göndərilməsi Faza 6-da
 * (VAPID/push_abuneler) əlavə olunacaq — bu skript indi audit jurnalına yazır ki,
 * həmin funksionallıq gələndə mənbə hazır olsun.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Abunelik;
use App\Models\LegalLog;

const XEBERDARLIQ_GUN = 3;
const XEBERDARLIQ_HADISE = 'abunelik_xeberdarlig';

$abunelikler = new Abunelik();
$legalLogs = new LegalLog();

$bagli = 0;
foreach ($abunelikler->bitmisAmmaAktivOlanlar() as $abunelik) {
    $abunelikler->setAktiv((int) $abunelik['id'], false);
    $legalLogs->yaz(null, null, 'abunelik_bagladi', [
        'kurye_id' => (int) $abunelik['kurye_id'], 'bitme' => $abunelik['bitme'],
    ], '127.0.0.1');
    $bagli++;
}

$artiqXeberdarEdilib = $legalLogs->buGunXeberdarEdilenKuryeIdler(XEBERDARLIQ_HADISE);
$xeberdarEdilen = 0;

foreach ($abunelikler->tezliklaBitecekler(XEBERDARLIQ_GUN) as $abunelik) {
    $kuryeId = (int) $abunelik['kurye_id'];
    if (in_array($kuryeId, $artiqXeberdarEdilib, true)) {
        continue;
    }

    $legalLogs->yaz(null, null, XEBERDARLIQ_HADISE, [
        'kurye_id' => $kuryeId, 'bitme' => $abunelik['bitme'],
    ], '127.0.0.1');
    $xeberdarEdilen++;
}

echo date('Y-m-d H:i:s') . " — {$bagli} abunə bağlandı, {$xeberdarEdilen} xəbərdarlıq yazıldı.\n";
