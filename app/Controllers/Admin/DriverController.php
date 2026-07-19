<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\View;

final class DriverController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $tab = (string) ($_GET['tab'] ?? 'all');
        $q = trim((string) ($_GET['q'] ?? ''));

        $where = ["role = 'driver'"];
        $args = [];

        if ($tab === 'pending') {
            $where[] = "driver_status = 'pending'";
        }

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

        $sql = 'SELECT u.*, vt.name_az as vt_name FROM users u
                LEFT JOIN vehicle_types vt ON vt.id = u.vehicle_type_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY (driver_status = "pending") DESC, u.created_at DESC LIMIT 200';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute($args);

        View::render('admin/drivers_index', [
            'pageTitle' => 'Sürücülər',
            'drivers' => $stmt->fetchAll(),
            'tab' => $tab,
            'q' => $q,
        ], 'layouts/admin');
    }

    public function show(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT * FROM users WHERE id = ? AND role = "driver" LIMIT 1');
        $stmt->execute([$id]);
        $driver = $stmt->fetch();
        if ($driver === false) {
            http_response_code(404);
            echo 'Tapılmadı';
            return;
        }

        $vehicleTypes = DB::conn()->query('SELECT * FROM vehicle_types ORDER BY sort_order')->fetchAll();

        $jobsStmt = DB::conn()->prepare(
            "SELECT l.*, o.price, fl.name_az as from_name, tl.name_az as to_name
             FROM offers o JOIN listings l ON l.id = o.listing_id
             JOIN locations fl ON fl.id = l.from_location_id
             JOIN locations tl ON tl.id = l.to_location_id
             WHERE o.driver_id = ? AND o.status = 'accepted'
             ORDER BY l.created_at DESC LIMIT 30"
        );
        $jobsStmt->execute([$id]);

        $paymentsStmt = DB::conn()->prepare('SELECT * FROM payments WHERE driver_id = ? ORDER BY created_at DESC LIMIT 20');
        $paymentsStmt->execute([$id]);

        View::render('admin/driver_show', [
            'pageTitle' => $driver['full_name'],
            'driver' => $driver,
            'vehicleTypes' => $vehicleTypes,
            'jobs' => $jobsStmt->fetchAll(),
            'payments' => $paymentsStmt->fetchAll(),
        ], 'layouts/admin');
    }

    public function approve(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];

        $trialDays = (int) \App\Core\Settings::get('trial_days', '30');
        $stmt = DB::conn()->prepare(
            "UPDATE users SET driver_status = 'approved', billing_status = 'trial',
                trial_until = DATE_ADD(CURDATE(), INTERVAL ? DAY)
             WHERE id = ? AND role = 'driver'"
        );
        $stmt->execute([$trialDays, $id]);

        \App\Core\WebPush::sendToUsersLocalized([$id], static fn () => [
            'title' => t('push.driver_approved_title'),
            'body' => t('push.driver_approved_body'),
            'url' => '/surucu/lent',
        ]);

        AdminAuth::log('driver_approve', 'user', $id);
        header('Location: /surucular/' . $id);
    }

    public function reject(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];
        $reason = trim((string) ($_POST['reason'] ?? ''));

        $stmt = DB::conn()->prepare("UPDATE users SET driver_status = 'rejected', reject_reason = ? WHERE id = ? AND role = 'driver'");
        $stmt->execute([$reason !== '' ? $reason : null, $id]);

        AdminAuth::log('driver_reject', 'user', $id, ['reason' => $reason]);
        header('Location: /surucular/' . $id);
    }

    /** Q-Y8: bir klik Pulsuz ↔ Pullu, anında. */
    public function toggleFree(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT billing_status FROM users WHERE id = ? AND role = "driver"');
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if ($current === false) {
            header('Location: /surucular');
            return;
        }

        if ($current['billing_status'] === 'free') {
            // Free-dən çıxarılanda trial-a qaytarılır (default trial_days ilə) — sadə/proqnozlaşdırıla bilən davranış.
            $trialDays = (int) \App\Core\Settings::get('trial_days', '30');
            $upd = DB::conn()->prepare(
                "UPDATE users SET billing_status = 'trial', trial_until = DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE id = ?"
            );
            $upd->execute([$trialDays, $id]);
            $newStatus = 'trial';
        } else {
            $upd = DB::conn()->prepare("UPDATE users SET billing_status = 'free' WHERE id = ?");
            $upd->execute([$id]);
            $newStatus = 'free';
        }

        AdminAuth::log('driver_billing_toggle', 'user', $id, ['new_status' => $newStatus]);
        header('Location: /surucular/' . $id);
    }

    /** Fərdi qiymət qoy/sil (Q-Y8). */
    public function setCustomPrice(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];
        $priceRaw = trim((string) ($_POST['custom_price'] ?? ''));

        $price = $priceRaw === '' ? null : (float) $priceRaw;
        $stmt = DB::conn()->prepare('UPDATE users SET custom_price = ? WHERE id = ? AND role = "driver"');
        $stmt->execute([$price, $id]);

        AdminAuth::log('driver_custom_price', 'user', $id, ['price' => $price]);
        header('Location: /surucular/' . $id);
    }

    public function block(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT is_blocked FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $isBlocked = (int) ($stmt->fetch()['is_blocked'] ?? 0);
        $newVal = $isBlocked === 1 ? 0 : 1;

        $upd = DB::conn()->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
        $upd->execute([$newVal, $id]);

        AdminAuth::log($newVal === 1 ? 'driver_block' : 'driver_unblock', 'user', $id);
        header('Location: /surucular/' . $id);
    }

    /** Sürücü şifrəsini itirib admin-dən dəstək istəyəndə birbaşa sıfırlama. */
    public function resetPassword(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];
        $new = (string) ($_POST['new_password'] ?? '');

        if (strlen($new) < 6) {
            header('Location: /surucular/' . $id . '?sifre_xeta=qisa');
            return;
        }

        $stmt = DB::conn()->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = "driver"');
        $stmt->execute([password_hash($new, PASSWORD_BCRYPT), $id]);

        AdminAuth::log('driver_reset_password', 'user', $id);
        header('Location: /surucular/' . $id . '?sifre=ok');
    }

    private function assertCsrf(): void
    {
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            exit;
        }
    }
}
