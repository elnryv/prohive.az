<?php
declare(strict_types=1);

/** Admin girişi (bölmə 9): 2 uğursuz→30san gecikmə, 5 uğursuz→15dəq IP kilidi. */
final class AdminLogin
{
    public function form(): void
    {
        if (AdminAuth::check()) {
            header('Location: /');
            exit;
        }
        $this->render();
    }

    public function submit(): void
    {
        if (AdminAuth::check()) {
            header('Location: /');
            exit;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->render(['Sessiya vaxtı bitib, formu yenidən göndərin.']);
            return;
        }

        $ip = RateLimit::clientIp();
        if (AdminAuth::isLocked($ip)) {
            $this->render(['Çox sayda uğursuz cəhd. 15 dəqiqə sonra yenidən cəhd edin.']);
            return;
        }

        AdminAuth::applyDelayIfNeeded($ip);

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $admin = $username !== '' ? AdminRepository::findByUsername($username) : null;
        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            AdminAuth::recordFailure($ip);
            $this->render(['İstifadəçi adı və ya şifrə yanlışdır.'], $username);
            return;
        }

        AdminAuth::clearFailures($ip);
        AdminRepository::touchLastLogin((int) $admin['id']);
        AdminAuth::login((int) $admin['id']);

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        AdminAuth::logout();
        header('Location: /giris');
        exit;
    }

    /** @param string[] $errors */
    private function render(array $errors = [], string $oldUsername = ''): void
    {
        View::render('admin/login', [
            'title' => 'Giriş — Admin',
            'errors' => $errors,
            'oldUsername' => $oldUsername,
        ], 'layout');
    }
}
