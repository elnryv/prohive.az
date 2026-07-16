<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\DB;
use App\Core\View;

final class PaymentController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $stmt = DB::conn()->query(
            "SELECT p.*, u.full_name, u.phone FROM payments p JOIN users u ON u.id = p.driver_id
             ORDER BY p.created_at DESC LIMIT 200"
        );

        $monthly = DB::conn()->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total, COUNT(*) as cnt
             FROM payments WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY ym ORDER BY ym"
        )->fetchAll();

        View::render('admin/payments_index', [
            'pageTitle' => 'Ödənişlər',
            'payments' => $stmt->fetchAll(),
            'monthly' => $monthly,
        ], 'layouts/admin');
    }
}
