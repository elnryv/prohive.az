<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\Sse;
use App\Core\View;

final class ListingController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $status = (string) ($_GET['status'] ?? '');
        $scope = (string) ($_GET['scope'] ?? '');

        $where = ['1=1'];
        $args = [];
        if ($status !== '') {
            $where[] = 'l.status = ?';
            $args[] = $status;
        }
        if ($scope !== '') {
            $where[] = 'l.scope = ?';
            $args[] = $scope;
        }

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ', u.full_name as customer_name ' . ListingRules::FROM_SQL . '
                JOIN users u ON u.id = l.customer_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY l.created_at DESC LIMIT 100';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($args);

        $reportsStmt = DB::conn()->query(
            "SELECT r.*, l.public_code, u.full_name as reporter_name
             FROM reports r JOIN listings l ON l.id = r.listing_id JOIN users u ON u.id = r.reporter_user_id
             WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 20"
        );

        View::render('admin/listings_index', [
            'pageTitle' => 'Elanlar',
            'listings' => $stmt->fetchAll(),
            'reports' => $reportsStmt->fetchAll(),
            'status' => $status,
            'scope' => $scope,
        ], 'layouts/admin');
    }

    public function show(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $id = (int) $params['id'];

        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ', u.full_name as customer_name, u.phone as customer_phone '
             . ListingRules::FROM_SQL . ' JOIN users u ON u.id = l.customer_id WHERE l.id = ? LIMIT 1';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        if ($listing === false) {
            http_response_code(404);
            echo 'Tapılmadı';
            return;
        }

        $offersStmt = DB::conn()->prepare(
            'SELECT o.*, u.full_name, u.phone FROM offers o JOIN users u ON u.id = o.driver_id WHERE o.listing_id = ? ORDER BY o.created_at'
        );
        $offersStmt->execute([$id]);

        $eventsStmt = DB::conn()->prepare('SELECT * FROM listing_events WHERE listing_id = ? ORDER BY created_at');
        $eventsStmt->execute([$id]);

        View::render('admin/listing_show', [
            'pageTitle' => 'Elan #' . $id,
            'listing' => $listing,
            'offers' => $offersStmt->fetchAll(),
            'events' => $eventsStmt->fetchAll(),
        ], 'layouts/admin');
    }

    public function remove(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare("UPDATE listings SET status = 'removed', removed_by = 'admin' WHERE id = ? AND status IN ('active','accepted')");
        $stmt->execute([$id]);

        $ev = DB::conn()->prepare('INSERT INTO listing_events (listing_id, event) VALUES (?, "removed")');
        $ev->execute([$id]);
        Sse::publish('feed', 'listing_closed', ['listing_id' => $id]);

        AdminAuth::log('listing_remove', 'listing', $id);
        header('Location: /elanlar/' . $id);
    }

    public function resolveReport(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];
        $action = (string) ($_POST['action'] ?? 'dismissed');
        $status = $action === 'resolve' ? 'resolved' : 'dismissed';

        $stmt = DB::conn()->prepare('UPDATE reports SET status = ?, resolved_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);

        AdminAuth::log('report_' . $status, 'report', $id);
        header('Location: /elanlar');
    }
}
