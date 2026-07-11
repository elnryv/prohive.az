<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Models\Abunelik;
use App\Models\Ayar;
use App\Models\Kurye;
use App\Models\LegalLog;
use App\Models\User;

/**
 * Abunə idarəsi — fərdi (bax bölmə 8.5) və qlobal (bax bölmə 8.5.1) səviyyələr,
 * həmçinin uğurlu ödənişdən sonra sistem tərəfindən avtomatik uzatma (bax bölmə
 * 9.1.1: "Uğurlu: ... abunə +1 ay uzanır").
 */
final class AbunelikService
{
    private const GLOBAL_AYAR_ADI = 'abune_rejimi';
    private const TIPLER = ['pulsuz', 'pullu'];
    private const REJIMLER = ['aktiv', 'dayandirilib'];

    private Abunelik $abunelikler;
    private Ayar $ayarlar;
    private Kurye $kuryeler;
    private User $users;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->abunelikler = new Abunelik();
        $this->ayarlar = new Ayar();
        $this->kuryeler = new Kurye();
        $this->users = new User();
        $this->legalLogs = new LegalLog();
    }

    /**
     * @throws ValidationException
     */
    public function status(int $kuryeId): array
    {
        $kurye = $this->kuryeler->findById($kuryeId);
        if ($kurye === null) {
            throw new ValidationException('Kuryer tapılmadı.');
        }

        $user = $this->users->findById((int) $kurye['user_id']);
        $abunelik = $this->abunelikler->sonuncu($kuryeId);
        $qlobalDayandirilib = $this->ayarlar->get(self::GLOBAL_AYAR_ADI) === 'dayandirilib';

        $label = $this->hesablaLabel($user, $abunelik, $qlobalDayandirilib);
        $qalanGun = null;

        if ($abunelik !== null && $label === 'aktiv') {
            $qalanGun = (int) (new \DateTimeImmutable($abunelik['bitme']))
                ->diff(new \DateTimeImmutable('today'))->days;
        }

        return [
            'label' => $label,
            'tip' => $abunelik['tip'] ?? null,
            'bitme' => $abunelik['bitme'] ?? null,
            'qalan_gun' => $qalanGun,
            'qlobal_dayandirilib' => $qlobalDayandirilib,
        ];
    }

    private function hesablaLabel(?array $user, ?array $abunelik, bool $qlobalDayandirilib): string
    {
        if ($qlobalDayandirilib) {
            return 'pulsuz_qlobal';
        }
        if ($user !== null && $user['status'] === 'bloklu') {
            return 'bloklu';
        }
        if ($abunelik === null || (int) $abunelik['aktiv'] === 0) {
            return 'bitib';
        }
        if ($abunelik['tip'] === 'pulsuz') {
            return 'pulsuz';
        }
        if ($abunelik['bitme'] >= (new \DateTimeImmutable('today'))->format('Y-m-d')) {
            return 'aktiv';
        }

        return 'bitib';
    }

    /**
     * @throws ValidationException
     */
    public function uzat(int $kuryeId, int $gun, string $sebeb, int $adminId, string $ip): array
    {
        $this->sebebYoxla($sebeb);
        $this->kuryeMovcudMu($kuryeId);

        if ($gun < 1 || $gun > 365) {
            throw new ValidationException('Gün sayı 1-365 arasında olmalıdır.');
        }

        $this->uzatMuddet($kuryeId, $gun);

        $this->legalLogs->yaz($adminId, null, 'abunelik_uzadildi', [
            'kurye_id' => $kuryeId, 'gun' => $gun, 'sebeb' => $sebeb,
        ], $ip);

        return $this->status($kuryeId);
    }

    /**
     * Uğurlu ödənişdən sonra sistem tərəfindən avtomatik uzatma — bax bölmə 9.1.1.
     * Admin idarəsindən fərqli olaraq səbəb/admin ID tələb etmir (özünə-xidmət,
     * aktor kimi kuryerin öz istifadəçi ID-si yazılır).
     *
     * @throws ValidationException
     */
    public function odenisIleUzat(int $kuryeId, int $gun, int $odenisId, string $ip): array
    {
        $kurye = $this->kuryeler->findById($kuryeId);
        if ($kurye === null) {
            throw new ValidationException('Kuryer tapılmadı.');
        }

        $this->uzatMuddet($kuryeId, $gun);
        $this->abunelikler->setTip((int) $this->abunelikler->sonuncu($kuryeId)['id'], 'pullu');

        $this->legalLogs->yaz((int) $kurye['user_id'], null, 'abunelik_odenisle_uzadildi', [
            'kurye_id' => $kuryeId, 'gun' => $gun, 'odenis_id' => $odenisId,
        ], $ip);

        return $this->status($kuryeId);
    }

    private function uzatMuddet(int $kuryeId, int $gun): void
    {
        $bugun = new \DateTimeImmutable('today');
        $mevcud = $this->abunelikler->sonuncu($kuryeId);

        if ($mevcud === null) {
            $bitme = $bugun->modify("+{$gun} days")->format('Y-m-d');
            $this->abunelikler->create($kuryeId, 'pullu', $bugun->format('Y-m-d'), $bitme);
        } else {
            $mevcudBitme = new \DateTimeImmutable($mevcud['bitme']);
            $baza = $mevcudBitme > $bugun ? $mevcudBitme : $bugun;
            $yeniBitme = $baza->modify("+{$gun} days")->format('Y-m-d');
            $this->abunelikler->uzatBitme((int) $mevcud['id'], $yeniBitme);
        }
    }

    /**
     * @throws ValidationException
     */
    public function tipDeyis(int $kuryeId, string $tip, string $sebeb, int $adminId, string $ip): array
    {
        $this->sebebYoxla($sebeb);
        $this->kuryeMovcudMu($kuryeId);

        if (!in_array($tip, self::TIPLER, true)) {
            throw new ValidationException('Abunə tipi düzgün deyil.');
        }

        $mevcud = $this->abunelikler->sonuncu($kuryeId);
        if ($mevcud === null) {
            throw new ValidationException('Bu kuryerin hələ abunə dövrü yoxdur — əvvəlcə "+gün" ilə yaradın.');
        }

        $this->abunelikler->setTip((int) $mevcud['id'], $tip);
        $this->legalLogs->yaz($adminId, null, 'abunelik_tip_deyisdi', [
            'kurye_id' => $kuryeId, 'tip' => $tip, 'sebeb' => $sebeb,
        ], $ip);

        return $this->status($kuryeId);
    }

    /**
     * @throws ValidationException
     */
    public function aktivlikDeyis(int $kuryeId, bool $aktiv, string $sebeb, int $adminId, string $ip): array
    {
        $this->sebebYoxla($sebeb);
        $this->kuryeMovcudMu($kuryeId);

        $mevcud = $this->abunelikler->sonuncu($kuryeId);
        if ($mevcud === null) {
            throw new ValidationException('Bu kuryerin hələ abunə dövrü yoxdur — əvvəlcə "+gün" ilə yaradın.');
        }

        $this->abunelikler->setAktiv((int) $mevcud['id'], $aktiv);
        $hadise = $aktiv ? 'abunelik_aktivlesdirildi' : 'abunelik_dayandirildi';
        $this->legalLogs->yaz($adminId, null, $hadise, ['kurye_id' => $kuryeId, 'sebeb' => $sebeb], $ip);

        return $this->status($kuryeId);
    }

    /**
     * @throws ValidationException
     */
    public function qlobalRejim(string $rejim, string $sebeb, int $adminId, string $ip): void
    {
        $this->sebebYoxla($sebeb);

        if (!in_array($rejim, self::REJIMLER, true)) {
            throw new ValidationException('Rejim "aktiv" və ya "dayandirilib" olmalıdır.');
        }

        $this->ayarlar->set(self::GLOBAL_AYAR_ADI, $rejim);
        $this->legalLogs->yaz($adminId, null, 'qlobal_abune_rejimi_deyisdi', [
            'rejim' => $rejim, 'sebeb' => $sebeb,
        ], $ip);
    }

    /**
     * @throws ValidationException
     */
    private function sebebYoxla(string $sebeb): void
    {
        if (trim($sebeb) === '') {
            throw new ValidationException('Səbəb sahəsi mütləqdir.');
        }
    }

    /**
     * @throws ValidationException
     */
    private function kuryeMovcudMu(int $kuryeId): void
    {
        if ($this->kuryeler->findById($kuryeId) === null) {
            throw new ValidationException('Kuryer tapılmadı.');
        }
    }
}
