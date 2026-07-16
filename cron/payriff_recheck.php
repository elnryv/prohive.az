<?php
declare(strict_types=1);

/**
 * Yarımçıq ödənişlərin yoxlanması (bölmə 8.5, hər 15 dəqiqədə bir).
 * İşə salma: php cron/payriff_recheck.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

$summary = ['checked' => 0, 'approved' => 0, 'declined' => 0, 'errors' => 0, 'expired' => 0];

if (PAYRIFF_SECRET_KEY === '') {
    echo '[' . date('c') . '] payriff_recheck.php: PAYRIFF_SECRET_KEY boşdur, keçirilir.' . PHP_EOL;
    exit(0);
}

$gateway = new PayriffProvider();

foreach (PaymentRepository::staleCreated() as $payment) {
    if (empty($payment['payriff_order_id'])) {
        continue;
    }
    $summary['checked']++;

    try {
        $status = $gateway->getOrderStatus($payment['payriff_order_id']);
    } catch (RuntimeException) {
        $summary['errors']++;
        continue;
    }

    if ($status === 'APPROVED') {
        PaymentRepository::applyApproval((int) $payment['id']);
        $summary['approved']++;
    } elseif (in_array($status, ['DECLINED', 'CANCELED'], true)) {
        PaymentRepository::markStatus((int) $payment['id'], strtolower($status));
        $summary['declined']++;
    }
}

$summary['expired'] = PaymentRepository::expireOldCreated();

echo '[' . date('c') . '] payriff_recheck.php tamamlandı: ' . json_encode($summary, JSON_UNESCAPED_UNICODE) . PHP_EOL;
