<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Phone;
use App\Core\Settings;
use App\Core\Upload;
use App\Core\View;

final class AuthController
{
    public function registerForm(): void
    {
        if (Auth::check()) {
            $this->redirectHome();
        }

        $role = $_GET['rol'] ?? null;
        $vehicleTypes = [];
        if ($role === 'surucu') {
            $stmt = DB::conn()->query('SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY sort_order');
            $vehicleTypes = $stmt->fetchAll();
        }

        View::render('site/register', [
            'pageTitle' => t('auth.register_title'),
            'role' => $role,
            'vehicleTypes' => $vehicleTypes,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(): void
    {
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $role = $_POST['rol'] ?? '';
        if ($role === 'musteri') {
            $this->registerCustomer();
            return;
        }
        if ($role === 'surucu') {
            $this->registerDriver();
            return;
        }
        http_response_code(400);
        echo 'Yanlış rol.';
    }

    private function validateCommon(array &$errors): array
    {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phoneRaw = (string) ($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($fullName === '') {
            $errors['full_name'] = 'auth.full_name_required';
        }

        $phone = Phone::normalize($phoneRaw);
        if ($phone === null) {
            $errors['phone'] = 'auth.invalid_phone';
        }

        if (strlen($password) < 6) {
            $errors['password'] = 'auth.password_too_short';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'auth.password_mismatch';
        }

        if ($phone !== null && $errors === []) {
            $stmt = DB::conn()->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            if ($stmt->fetch() !== false) {
                $errors['phone'] = 'auth.phone_taken';
            }
        }

        return ['full_name' => $fullName, 'phone' => $phone, 'password' => $password];
    }

    private function registerCustomer(): void
    {
        $errors = [];
        $data = $this->validateCommon($errors);

        if ($errors !== []) {
            View::render('site/register', [
                'pageTitle' => t('auth.register_title'),
                'role' => 'musteri',
                'vehicleTypes' => [],
                'errors' => $errors,
                'old' => $_POST,
            ]);
            return;
        }

        $stmt = DB::conn()->prepare(
            'INSERT INTO users (role, phone, password_hash, full_name, lang) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'customer',
            $data['phone'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['full_name'],
            \App\Core\Lang::current(),
        ]);

        Auth::establishSession((int) DB::conn()->lastInsertId());
        header('Location: /musteri/elanlarim?xosgeldin=1');
    }

    private function registerDriver(): void
    {
        $errors = [];
        $data = $this->validateCommon($errors);

        $vehicleTypeId = (int) ($_POST['vehicle_type_id'] ?? 0);
        $vehicleNote = trim((string) ($_POST['vehicle_note'] ?? ''));

        if ($vehicleTypeId <= 0) {
            $errors['vehicle_type_id'] = 'auth.vehicle_type_required';
        }

        $uploadedFiles = $_FILES['vehicle_photos'] ?? null;
        $photoFilenames = [];
        if ($uploadedFiles === null || empty($uploadedFiles['name'][0])) {
            $errors['vehicle_photo'] = 'auth.vehicle_photo_required';
        } else {
            $count = count($uploadedFiles['name']);
            if ($count > 3) {
                $errors['vehicle_photo'] = 'auth.vehicle_photo_max';
            } else {
                for ($i = 0; $i < $count; $i++) {
                    if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $file = [
                        'name' => $uploadedFiles['name'][$i],
                        'type' => $uploadedFiles['type'][$i],
                        'tmp_name' => $uploadedFiles['tmp_name'][$i],
                        'error' => $uploadedFiles['error'][$i],
                        'size' => $uploadedFiles['size'][$i],
                    ];
                    $result = Upload::storeImage($file, (string) Config::get('upload.vehicle_photo_dir'), (int) Config::get('upload.max_photo_bytes'));
                    if ($result['ok']) {
                        $photoFilenames[] = $result['filename'];
                    }
                }
                if ($photoFilenames === []) {
                    $errors['vehicle_photo'] = 'auth.vehicle_photo_required';
                }
            }
        }

        if ($errors !== []) {
            $stmt = DB::conn()->query('SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY sort_order');
            View::render('site/register', [
                'pageTitle' => t('auth.register_title'),
                'role' => 'surucu',
                'vehicleTypes' => $stmt->fetchAll(),
                'errors' => $errors,
                'old' => $_POST,
            ]);
            return;
        }

        $trialDays = (int) Settings::get('trial_days', '30');

        // vehicle_photo cədvəldə tək sütundur (bölmə 4.2) — 1-3 foto vergüllə ayrılmış fayl
        // adları kimi saxlanılır (sxem dəyişdirilmir, yalnız təqdimat qatında bölünür).
        $stmt = DB::conn()->prepare(
            'INSERT INTO users (role, phone, password_hash, full_name, lang, vehicle_type_id, vehicle_note,
                vehicle_photo, driver_status, billing_status, trial_until)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))'
        );
        $stmt->execute([
            'driver',
            $data['phone'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['full_name'],
            \App\Core\Lang::current(),
            $vehicleTypeId,
            $vehicleNote !== '' ? $vehicleNote : null,
            implode(',', $photoFilenames),
            'pending',
            'trial',
            $trialDays,
        ]);

        Auth::establishSession((int) DB::conn()->lastInsertId());
        header('Location: /surucu/lent?xosgeldin=1');
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirectHome();
        }
        View::render('site/login', ['pageTitle' => t('auth.login_title'), 'error' => null]);
    }

    public function login(): void
    {
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $phone = (string) ($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        $result = Auth::login($phone, $password, $remember);

        if (!$result['ok']) {
            View::render('site/login', ['pageTitle' => t('auth.login_title'), 'error' => $result['error']]);
            return;
        }

        $this->redirectHome();
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
    }

    private function redirectHome(): void
    {
        $user = Auth::user();
        if ($user !== null && $user['role'] === 'driver') {
            header('Location: /surucu/lent');
        } else {
            header('Location: /musteri/elanlarim');
        }
        exit;
    }
}
