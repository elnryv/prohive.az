<?php
declare(strict_types=1);

/** Giriş (7.2): Telefon + şifrə. 5 uğursuz cəhd → 15 dəq kilid (IP+nömrə). */
final class Login
{
    public function form(): void
    {
        if (Auth::check()) {
            header('Location: /sahib/panel');
            exit;
        }
        $this->render();
    }

    public function submit(): void
    {
        if (Auth::check()) {
            header('Location: /sahib/panel');
            exit;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->render(['Sessiya vaxtı bitib, formu yenidən göndərin.']);
            return;
        }

        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        $phone = null;
        try {
            $phone = Phone::normalize($phoneRaw);
        } catch (InvalidArgumentException) {
            // aşağıda ümumi "yanlış" mesajı ilə davam edir
        }

        $lockKey = RateLimit::clientIp() . ':' . ($phone ?? $phoneRaw);
        if (RateLimit::tooMany('login', $lockKey, 5, 900)) {
            $this->render(['Çox sayda uğursuz cəhd. 15 dəqiqə sonra yenidən cəhd edin.'], ['phone' => $phoneRaw]);
            return;
        }

        $owner = $phone !== null ? OwnerRepository::findByPhone($phone) : null;
        if ($owner === null || !password_verify($password, $owner['password_hash'])) {
            RateLimit::hit('login', $lockKey, 900);
            $this->render(['Telefon nömrəsi və ya şifrə yanlışdır.'], ['phone' => $phoneRaw]);
            return;
        }

        RateLimit::clear('login', $lockKey);

        if (password_needs_rehash($owner['password_hash'], PASSWORD_DEFAULT)) {
            DB::query(
                'UPDATE owners SET password_hash = :h WHERE id = :id',
                [':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $owner['id']]
            );
        }

        OwnerRepository::touchLastLogin((int) $owner['id']);
        Auth::login((int) $owner['id'], $remember);

        header('Location: /sahib/panel');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
        exit;
    }

    /** @param string[] $errors @param array<string,string> $old */
    private function render(array $errors = [], array $old = []): void
    {
        View::render('owner/login', [
            'title' => Lang::t('owner.login_title') . ' — ' . Lang::t('app.name'),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
