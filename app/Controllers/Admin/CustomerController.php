<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\View;

final class CustomerController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');
        $q = trim((string) ($_GET['q'] ?? ''));

        $where = ["role = 'customer'"];
        $args = [];
        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q);
            if ($digits !== '' && strlen($digits) >= 3) {
                $where[] = '(phone LIKE ? OR full_name LIKE ?)';
                $args[] = '%' . $digits . '%';
                $args[] = '%' . $q . '%';
            } else {
                $where[] = 'full_name LIKE ?';
                $args[] = '%' . $q . '%';
            }
        }

        $sql = "SELECT u.*,
                (SELECT COUNT(*) FROM listings WHERE customer_id = u.id) as listing_count,
                (SELECT COUNT(*) FROM listings WHERE customer_id = u.id AND status IN ('accepted','completed')) as accepted_count,
                (SELECT COALESCE(SUM(reopen_count),0) FROM listings WHERE customer_id = u.id) as cancel_count
                FROM users u WHERE " . implode(' AND ', $where) . ' ORDER BY u.created_at DESC LIMIT 200';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($args);

        View::render('admin/customers_index', [
            'pageTitle' => 'Müştərilər',
            'customers' => $stmt->fetchAll(),
            'q' => $q,
        ], 'layouts/admin');
    }

    public function show(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT * FROM users WHERE id = ? AND role = "customer" LIMIT 1');
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if ($customer === false) {
            http_response_code(404);
            echo 'Tapılmadı';
            return;
        }

        $listingsStmt = DB::conn()->prepare(
            "SELECT l.*, c.icon, c.name_az as cat_name, fl.name_az as from_name, tl.name_az as to_name
             FROM listings l
             JOIN categories c ON c.id = l.category_id
             JOIN locations fl ON fl.id = l.from_location_id
             JOIN locations tl ON tl.id = l.to_location_id
             WHERE l.customer_id = ? ORDER BY l.created_at DESC LIMIT 50"
        );
        $listingsStmt->execute([$id]);

        View::render('admin/customer_show', [
            'pageTitle' => $customer['full_name'],
            'customer' => $customer,
            'listings' => $listingsStmt->fetchAll(),
        ], 'layouts/admin');
    }

    public function block(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT is_blocked FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $isBlocked = (int) ($stmt->fetch()['is_blocked'] ?? 0);
        $newVal = $isBlocked === 1 ? 0 : 1;

        $upd = DB::conn()->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
        $upd->execute([$newVal, $id]);

        AdminAuth::log($newVal === 1 ? 'customer_block' : 'customer_unblock', 'user', $id);
        header('Location: /musteriler/' . $id);
    }
}
