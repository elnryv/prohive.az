<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (AdminAuth::check()) {
            header('Location: /dashboard');
            exit;
        }
        View::render('admin/login', ['pageTitle' => 'Giriş', 'error' => null], 'layouts/admin');
    }

    public function login(): void
    {
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $result = AdminAuth::login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
        if (!$result['ok']) {
            View::render('admin/login', ['pageTitle' => 'Giriş', 'error' => $result['error']], 'layouts/admin');
            return;
        }

        header('Location: /dashboard');
    }

    public function logout(): void
    {
        AdminAuth::logout();
        header('Location: /giris');
    }
}
