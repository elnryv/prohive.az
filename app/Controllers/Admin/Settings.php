<?php
declare(strict_types=1);

/** Ümumi qiymət və sistem parametrləri (bölmə 9.4). */
final class Settings
{
    public function index(): void
    {
        AdminAuth::requireLogin();
        $this->render();
    }

    public function update(): void
    {
        AdminAuth::requireLogin();
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->render(['Sessiya vaxtı bitib, formu yenidən göndərin.']);
            return;
        }

        $price = (string) ($_POST['default_monthly_price'] ?? '');
        $trialDays = (string) ($_POST['trial_days'] ?? '');
        $sitePhone = trim((string) ($_POST['site_phone'] ?? ''));
        $maintenance = isset($_POST['maintenance_mode']) ? '1' : '0';

        $errors = [];
        if (!is_numeric($price) || (float) $price <= 0) {
            $errors[] = 'Ümumi qiymət düzgün deyil.';
        }
        if (!ctype_digit($trialDays) || (int) $trialDays < 1) {
            $errors[] = 'Trial günü düzgün deyil.';
        }
        if ($sitePhone !== '') {
            try {
                $sitePhone = Phone::normalize($sitePhone);
            } catch (InvalidArgumentException) {
                $errors[] = 'Dəstək nömrəsi düzgün formatda deyil.';
            }
        }

        if ($errors !== []) {
            $this->render($errors);
            return;
        }

        SettingsRepository::set('default_monthly_price', number_format((float) $price, 2, '.', ''));
        SettingsRepository::set('trial_days', $trialDays);
        SettingsRepository::set('site_phone', $sitePhone);
        SettingsRepository::set('maintenance_mode', $maintenance);

        AdminLog::write('settings.update', null, "price={$price}, trial_days={$trialDays}");

        $this->render([], true);
    }

    /** @param string[] $errors */
    private function render(array $errors = [], bool $saved = false): void
    {
        View::render('admin/settings', [
            'title' => 'Parametrlər — Admin',
            'errors' => $errors,
            'saved' => $saved,
            'defaultPrice' => SettingsRepository::get('default_monthly_price', '25.00'),
            'trialDays' => SettingsRepository::get('trial_days', (string) TRIAL_DAYS),
            'sitePhone' => SettingsRepository::get('site_phone', ''),
            'maintenanceMode' => SettingsRepository::get('maintenance_mode', '0') === '1',
        ], 'layout');
    }
}
