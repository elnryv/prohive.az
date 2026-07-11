<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Banner;
use App\Models\Kurye;
use App\Models\LegalLog;
use App\Models\Sifaris;
use App\Models\User;

/**
 * Admin Dashboard — canlı sayğaclar, son hadisələr (bax bölmə 8.1).
 */
final class AdminDashboardService
{
    private const MUSTERI_ROLLAR = ['musteri'];
    private const KURYE_ROLLAR = ['kurye', 'yukdasima'];
    private const SON_HADISE_SAYI = 20;

    private User $users;
    private Kurye $kuryeler;
    private Sifaris $sifarisler;
    private Banner $bannerler;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->users = new User();
        $this->kuryeler = new Kurye();
        $this->sifarisler = new Sifaris();
        $this->bannerler = new Banner();
        $this->legalLogs = new LegalLog();
    }

    public function sayğaclar(): array
    {
        return [
            'musteri_sayi' => $this->users->countByRoller(self::MUSTERI_ROLLAR, null),
            'kurye_sayi' => $this->users->countByRoller(self::KURYE_ROLLAR, null),
            'onlayn_kurye_sayi' => $this->kuryeler->countOnlayn(),
            'bloklu_istifadeci_sayi' => $this->users->countByStatus('bloklu'),
            'axtarisda_sifaris_sayi' => $this->sifarisler->countByStatus('axtarisda'),
            'goturulmus_sifaris_sayi' => $this->sifarisler->countByStatus('goturulub'),
            'tamamlanan_sifaris_sayi' => $this->sifarisler->countByStatus('tamamlandi'),
            'legv_sifaris_sayi' => $this->sifarisler->countByStatus('legv'),
            'passiv_sifaris_sayi' => $this->sifarisler->countByStatus('passiv'),
            'aktiv_banner_sayi' => $this->bannerler->countAktiv(),
        ];
    }

    public function sonHadiseler(): array
    {
        return $this->legalLogs->sonuncular(self::SON_HADISE_SAYI);
    }
}
