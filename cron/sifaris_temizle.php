<?php

declare(strict_types=1);

/**
 * CLI: php cron/sifaris_temizle.php (saatlıq işə salınmalıdır — bax deploy/VPS_QURULUM.md).
 * 1 saatdan çox 'axtarisda' qalan sifarişləri 'passiv' statusuna keçirir (bax bölmə 6.1/6.5).
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\SifarisService;

$service = new SifarisService();
$count = $service->passivlesdirKohneleri();

echo date('Y-m-d H:i:s') . " — {$count} sifariş passivləşdirildi.\n";
