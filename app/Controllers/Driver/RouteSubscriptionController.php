<?php

declare(strict_types=1);

namespace App\Controllers\Driver;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\View;

/** Marşrut abunəliyi (Q-Y12, bölmə 4.12): maks 5 abunə/sürücü. */
final class RouteSubscriptionController
{
    private const MAX_ROUTES = 5;

    public function index(): void
    {
        Auth::requireRole('driver', '/giris');
        $driverId = (int) Auth::id();

        $stmt = DB::conn()->prepare(
            'SELECT rs.*, fl.name_az as from_az, fl.name_ru as from_ru, fl.name_en as from_en,
                    tl.name_az as to_az, tl.name_ru as to_ru, tl.name_en as to_en
             FROM route_subscriptions rs
             LEFT JOIN locations fl ON fl.id = rs.from_location_id
             LEFT JOIN locations tl ON tl.id = rs.to_location_id
             WHERE rs.driver_id = ?'
        );
        $stmt->execute([$driverId]);

        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        View::render('driver/routes', [
            'pageTitle' => t('routes.title'),
            'routes' => $stmt->fetchAll(),
            'locations' => $locations,
            'canAddMore' => $stmt->rowCount() < self::MAX_ROUTES,
        ]);
    }

    public function add(): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $driverId = (int) Auth::id();

        $countStmt = DB::conn()->prepare('SELECT COUNT(*) c FROM route_subscriptions WHERE driver_id = ?');
        $countStmt->execute([$driverId]);
        if ((int) $countStmt->fetch()['c'] >= self::MAX_ROUTES) {
            header('Location: /surucu/marsrutlar?xeta=limit');
            return;
        }

        $from = (int) ($_POST['from_location_id'] ?? 0) ?: null;
        $to = (int) ($_POST['to_location_id'] ?? 0) ?: null;
        $scope = in_array($_POST['scope'] ?? 'all', ['baku', 'intercity', 'all'], true) ? $_POST['scope'] : 'all';

        $stmt = DB::conn()->prepare(
            'INSERT IGNORE INTO route_subscriptions (driver_id, from_location_id, to_location_id, scope) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$driverId, $from, $to, $scope]);

        header('Location: /surucu/marsrutlar');
    }

    public function remove(array $params): void
    {
        Auth::requireRole('driver', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];
        $stmt = DB::conn()->prepare('DELETE FROM route_subscriptions WHERE id = ? AND driver_id = ?');
        $stmt->execute([$id, Auth::id()]);
        header('Location: /surucu/marsrutlar');
    }
}
