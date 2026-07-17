<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Billing;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Settings;
use App\Core\View;
use App\Core\WebPush;

/** Parametrlər (bölmə 9.5) — Q-Y7 BURADADIR: ödəniş açarı toggle-i. */
final class SettingsController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $categories = DB::conn()->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations ORDER BY is_baku DESC, sort_order')->fetchAll();
        $vehicleTypes = DB::conn()->query('SELECT * FROM vehicle_types ORDER BY sort_order')->fetchAll();

        View::render('admin/settings', [
            'pageTitle' => 'Parametrlər',
            'settings' => [
                'payments_enabled' => Settings::get('payments_enabled', '1'),
                'default_monthly_price' => Settings::get('default_monthly_price', '25.00'),
                'trial_days' => Settings::get('trial_days', '30'),
                'grace_days' => Settings::get('grace_days', '7'),
                'listing_auto_close_hours' => Settings::get('listing_auto_close_hours', '72'),
                'support_phone' => Settings::get('support_phone', ''),
                'maintenance_mode' => Settings::get('maintenance_mode', '0'),
            ],
            'categories' => $categories,
            'locations' => $locations,
            'vehicleTypes' => $vehicleTypes,
        ], 'layouts/admin');
    }

    /** Q-Y7: böyük toggle "Abunə sistemi: AKTİV / SÖNÜLÜ". */
    public function togglePayments(): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();

        $current = Settings::get('payments_enabled', '1');
        $newValue = $current === '1' ? '0' : '1';
        Settings::set('payments_enabled', $newValue);

        $affectedDriverIds = [];
        if ($newValue === '1') {
            $affectedDriverIds = Billing::applyGraceOnEnable();
            if ($affectedDriverIds !== []) {
                $graceDays = Settings::get('grace_days', '7');
                WebPush::sendToUsersLocalized($affectedDriverIds, static fn () => [
                    'title' => t('push.billing_reminder_title'),
                    'body' => t('billing.system_activated_body', ['days' => $graceDays]),
                    'url' => '/surucu/odenis',
                ]);
            }
        }

        AdminAuth::log('settings_toggle_payments', 'settings', null, [
            'new_value' => $newValue,
            'grace_applied_to' => $affectedDriverIds,
        ]);

        header('Location: /parametrler');
    }

    public function updateGeneral(): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();

        $fields = ['default_monthly_price', 'trial_days', 'grace_days', 'listing_auto_close_hours', 'support_phone'];
        $changes = [];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = trim((string) $_POST[$field]);
                $old = Settings::get($field);
                if ($old !== $value) {
                    Settings::set($field, $value);
                    $changes[$field] = ['from' => $old, 'to' => $value];
                }
            }
        }

        if ($changes !== []) {
            // Q-Y8: ümumi qiymət dəyişimi fərdi (custom_price qeydli) sürücülərə TƏSİR ETMİR —
            // Billing::amountFor() onsuz da custom_price varsa onu üstün tutur, əlavə əməliyyat lazım deyil.
            AdminAuth::log('settings_update_general', 'settings', null, $changes);
        }

        header('Location: /parametrler');
    }

    public function toggleMaintenance(): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();

        $current = Settings::get('maintenance_mode', '0');
        $newValue = $current === '1' ? '0' : '1';
        Settings::set('maintenance_mode', $newValue);

        AdminAuth::log('settings_toggle_maintenance', 'settings', null, ['new_value' => $newValue]);
        header('Location: /parametrler');
    }

    public function changePassword(): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $adminId = AdminAuth::id();
        $stmt = DB::conn()->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();

        if ($row === false || !password_verify($current, $row['password_hash'])) {
            header('Location: /parametrler?sifre_xeta=cari_yanlis');
            return;
        }
        if (strlen($new) < 6) {
            header('Location: /parametrler?sifre_xeta=qisa');
            return;
        }
        if ($new !== $confirm) {
            header('Location: /parametrler?sifre_xeta=uygun_deyil');
            return;
        }

        DB::conn()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT), $adminId]);

        AdminAuth::log('admin_change_password', 'admin', $adminId);
        header('Location: /parametrler?sifre=ok');
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
