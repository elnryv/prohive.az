<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Core\ValidationException;
use App\Models\Admin;
use App\Models\LegalLog;

/**
 * Admin giriş/çıxış/şifrə dəyişmə — bax CLAUDE.md bölmə 8.8. Remember-me YOXDUR,
 * sessiya qısa idle-timeout ilə idarə olunur (bax AdminAuth middleware).
 */
final class AdminAuthService
{
    // Bax AuthService::DUMMY_HASH qeydi — mövcud olmayan admin hesabı üçün
    // timing side-channel yaranmasın deyə.
    private const DUMMY_HASH = '$2y$12$RY.MHhCqtOeyxmLALCHwCug1aUvP9mE4dwnEJp/jlAGeVMc6nmeHO';

    private Admin $adminlər;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->adminlər = new Admin();
        $this->legalLogs = new LegalLog();
    }

    /**
     * @throws ValidationException
     */
    public function giris(string $telefonRaw, string $parol, string $ip): array
    {
        $telefon = (string) preg_replace('/\D/', '', $telefonRaw);
        $admin = $this->adminlər->findByTelefon($telefon);
        $parolDogrudur = password_verify($parol, $admin['parol_hash'] ?? self::DUMMY_HASH);

        if ($admin === null || !$parolDogrudur) {
            throw new ValidationException('Telefon nömrəsi və ya parol yanlışdır.');
        }
        if ($admin['status'] !== 'aktiv') {
            throw new ValidationException('Admin hesabı bloklanıb.');
        }

        Session::regenerate();
        Session::set('admin_id', (int) $admin['id']);
        Session::set('_admin_last_activity', time());

        $this->legalLogs->yaz((int) $admin['id'], null, 'admin_giris', [], $ip);

        return $admin;
    }

    public function cixis(): void
    {
        Session::destroy();
    }

    /**
     * @throws ValidationException
     */
    public function parolDeyis(int $adminId, string $cariParol, string $yeniParol, string $ip): void
    {
        $admin = $this->adminlər->findById($adminId);
        if ($admin === null || !password_verify($cariParol, $admin['parol_hash'])) {
            throw new ValidationException('Cari parol yanlışdır.');
        }
        if (strlen($yeniParol) < 8) {
            throw new ValidationException('Yeni parol ən azı 8 simvol olmalıdır.');
        }

        $this->adminlər->updateParolHash($adminId, password_hash($yeniParol, PASSWORD_DEFAULT));
        $this->legalLogs->yaz($adminId, null, 'admin_parol_deyis', [], $ip);
    }
}
