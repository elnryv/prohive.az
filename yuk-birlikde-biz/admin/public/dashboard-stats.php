<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();

function dashboard_stats(PDO $db): array
{
    $today = date('Y-m-d');

    $lastEventCreatedAt = db_scalar($db, 'SELECT created_at FROM events ORDER BY id DESC LIMIT 1');

    return [
        'total_customers' => (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE role = 'customer'"),
        'total_drivers' => (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE role = 'driver'"),
        'active_customers' => (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'"),
        'active_drivers' => (int) db_scalar($db, "SELECT COUNT(*) FROM users WHERE role = 'driver' AND status = 'active'"),
        'today_registrations' => (int) db_scalar($db, 'SELECT COUNT(*) FROM users WHERE DATE(created_at) = :today', ['today' => $today]),
        'active_orders' => (int) db_scalar($db, "SELECT COUNT(*) FROM orders WHERE status IN ('active','waiting','negotiating')"),
        'closed_today' => (int) db_scalar($db, "SELECT COUNT(*) FROM orders WHERE status = 'closed' AND DATE(updated_at) = :today", ['today' => $today]),
        'pending_complaints' => (int) db_scalar($db, "SELECT COUNT(*) FROM complaints WHERE status = 'new'"),
        'active_subscriptions' => (int) db_scalar($db, "SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at > NOW()"),
        'expiring_subscriptions' => (int) db_scalar($db, "SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)"),
        'today_payments_amount' => (float) db_scalar($db, "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success' AND DATE(created_at) = :today", ['today' => $today]),
        'today_payments_count' => (int) db_scalar($db, "SELECT COUNT(*) FROM payments WHERE status = 'success' AND DATE(created_at) = :today", ['today' => $today]),
        'last_event_age_seconds' => $lastEventCreatedAt ? (time() - strtotime((string) $lastEventCreatedAt)) : null,
        'disk_free_gb' => round((float) @disk_free_space(__DIR__) / 1073741824, 1),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(dashboard_stats(db()), JSON_UNESCAPED_UNICODE);
