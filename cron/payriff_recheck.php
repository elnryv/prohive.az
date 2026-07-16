<?php

declare(strict_types=1);

/**
 * Payriff pending ödənişlərin təhlükəsizlik toru (bölmə 8, 12.2) — 15 dəqiqədə bir işə
 * salınır (crontab). Callback heç vaxt gəlmədiyi hallar üçün statusu yenidən sorğulayır.
 */

require __DIR__ . '/bootstrap.php';

use App\Core\DB;
use App\Controllers\Site\PaymentCallbackController;

$stmt = DB::conn()->query(
    "SELECT * FROM payments WHERE status = 'pending' AND payriff_order_id IS NOT NULL
     AND created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)"
);
$payments = $stmt->fetchAll();

$controller = new PaymentCallbackController();
$checked = 0;
foreach ($payments as $payment) {
    $controller->reconcile($payment);
    $checked++;
}

echo $checked . " pending ödəniş yenidən yoxlanıldı.\n";
