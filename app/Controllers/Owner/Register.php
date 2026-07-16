<?php
declare(strict_types=1);

/** Qeydiyyat (7.1): Ad Soyad, Telefon, Şifrə, Şifrə təkrar, Şərtlərlə razılıq. OTP/SMS YOXDUR (Q2). */
final class Register
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

        $ip = RateLimit::clientIp();
        if (RateLimit::tooMany('register', $ip, 3, 3600)) {
            $this->render(['Çox sayda qeydiyyat cəhdi. Zəhmət olmasa 1 saat sonra yenidən cəhd edin.']);
            return;
        }
        RateLimit::hit('register', $ip, 3600);

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');
        $agree = isset($_POST['agree']);

        $errors = [];
        if (mb_strlen($fullName) < 2) {
            $errors[] = 'Ad Soyad tələb olunur.';
        }

        $phone = null;
        try {
            $phone = Phone::normalize($phoneRaw);
        } catch (InvalidArgumentException) {
            $errors[] = 'Telefon nömrəsi düzgün formatda deyil.';
        }

        if (mb_strlen($password) < 6) {
            $errors[] = 'Şifrə minimum 6 simvol olmalıdır.';
        }
        if ($password !== $password2) {
            $errors[] = 'Şifrələr uyğun gəlmir.';
        }
        if (!$agree) {
            $errors[] = 'Şərtlərlə razılaşmalısınız.';
        }
        if ($phone !== null && OwnerRepository::findByPhone($phone) !== null) {
            $errors[] = 'Bu telefon nömrəsi ilə artıq qeydiyyat mövcuddur.';
        }

        if ($errors !== []) {
            $this->render($errors, ['full_name' => $fullName, 'phone' => $phoneRaw]);
            return;
        }

        $trialDays = SettingsRepository::getInt('trial_days', TRIAL_DAYS);
        $ownerId = OwnerRepository::create(
            (string) $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $fullName,
            $trialDays
        );

        Sse::emit('admin', 'new_owner', ['owner_id' => $ownerId, 'full_name' => $fullName, 'phone' => (string) $phone]);
        Auth::login($ownerId, true);
        header('Location: /sahib/ev/yeni?xosgeldin=1');
        exit;
    }

    /** @param string[] $errors @param array<string,string> $old */
    private function render(array $errors = [], array $old = []): void
    {
        View::render('owner/register', [
            'title' => Lang::t('owner.register_title') . ' — ' . Lang::t('app.name'),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
