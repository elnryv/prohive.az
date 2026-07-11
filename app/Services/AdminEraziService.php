<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Models\LegalLog;
use App\Models\Rayon;
use App\Models\Sehir;

/**
 * Admin panel — Ərazi İdarəsi (bax bölmə 8.7). Hardcode yox, hamısı DB-dən;
 * admin şəhər/rayon əlavə edə, deaktiv/aktiv edə, (istifadə olunmayanı) silə bilər.
 */
final class AdminEraziService
{
    private Sehir $sehirler;
    private Rayon $rayonlar;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->sehirler = new Sehir();
        $this->rayonlar = new Rayon();
        $this->legalLogs = new LegalLog();
    }

    public function sehirSiyahi(): array
    {
        return $this->sehirler->listAll();
    }

    /**
     * @throws ValidationException
     */
    public function sehirYarat(string $adAz, ?string $adRu, ?string $adEn, int $adminId, string $ip): array
    {
        $adAz = trim($adAz);
        if ($adAz === '') {
            throw new ValidationException('Şəhər adı mütləqdir.');
        }
        if ($this->sehirler->existsByAd($adAz)) {
            throw new ValidationException('Bu adda şəhər artıq mövcuddur.');
        }

        $id = $this->sehirler->create($adAz, $this->bosMuNull($adRu), $this->bosMuNull($adEn));
        $this->legalLogs->yaz($adminId, null, 'sehir_yaradildi', ['sehir_id' => $id, 'ad_az' => $adAz], $ip);

        return $this->sehirler->findById($id) ?? [];
    }

    /**
     * @throws ValidationException
     */
    public function rayonlarBySehir(int $sehirId): array
    {
        if ($this->sehirler->findById($sehirId) === null) {
            throw new ValidationException('Şəhər tapılmadı.');
        }

        return $this->rayonlar->listBySehir($sehirId);
    }

    /**
     * @throws ValidationException
     */
    public function rayonYarat(int $sehirId, string $adAz, ?string $adRu, ?string $adEn, int $adminId, string $ip): array
    {
        if ($this->sehirler->findById($sehirId) === null) {
            throw new ValidationException('Şəhər tapılmadı.');
        }

        $adAz = trim($adAz);
        if ($adAz === '') {
            throw new ValidationException('Ərazi adı mütləqdir.');
        }
        if ($this->rayonlar->existsByAdInSehir($sehirId, $adAz)) {
            throw new ValidationException('Bu şəhərdə bu adda ərazi artıq mövcuddur.');
        }

        $id = $this->rayonlar->create($sehirId, $adAz, $this->bosMuNull($adRu), $this->bosMuNull($adEn));
        $this->legalLogs->yaz($adminId, null, 'rayon_yaradildi', [
            'rayon_id' => $id, 'sehir_id' => $sehirId, 'ad_az' => $adAz,
        ], $ip);

        return $this->rayonlar->findById($id) ?? [];
    }

    /**
     * @throws ValidationException
     */
    public function rayonAktivlikDeyis(int $rayonId, bool $aktiv, int $adminId, string $ip): void
    {
        if ($this->rayonlar->findById($rayonId) === null) {
            throw new ValidationException('Ərazi tapılmadı.');
        }

        $this->rayonlar->setAktiv($rayonId, $aktiv);
        $hadise = $aktiv ? 'rayon_aktivlesdirildi' : 'rayon_deaktivlesdirildi';
        $this->legalLogs->yaz($adminId, null, $hadise, ['rayon_id' => $rayonId], $ip);
    }

    /**
     * Yalnız heç bir sifarişdə (və kuryerin seçdiyi ərazilərdə) istifadə olunmayan
     * ərazi tam silinə bilər — bax bölmə 8.7 (referential integrity).
     *
     * @throws ValidationException
     */
    public function rayonSil(int $rayonId, int $adminId, string $ip): void
    {
        if ($this->rayonlar->findById($rayonId) === null) {
            throw new ValidationException('Ərazi tapılmadı.');
        }
        if ($this->rayonlar->isUsedInSifarisler($rayonId)) {
            throw new ValidationException('Bu ərazi sifarişlərdə istifadə olunub, silinə bilməz — deaktiv edin.');
        }
        if ($this->rayonlar->isUsedInKuryeBolgeler($rayonId)) {
            throw new ValidationException('Bu ərazini seçmiş kuryerlər var, silinə bilməz — deaktiv edin.');
        }

        $this->rayonlar->delete($rayonId);
        $this->legalLogs->yaz($adminId, null, 'rayon_silindi', ['rayon_id' => $rayonId], $ip);
    }

    private function bosMuNull(?string $deyer): ?string
    {
        $deyer = trim((string) $deyer);

        return $deyer === '' ? null : $deyer;
    }
}
