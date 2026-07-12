<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Core\WhatsApp;
use App\Models\DasiyiciOlcusu;
use App\Models\Kurye;
use App\Models\KuryeBolge;
use App\Models\LegalLog;
use App\Models\Rayon;
use App\Models\Sifaris;
use App\Models\User;
use App\Models\YukdasimaOlcusu;

/**
 * Sifariş axını və SSE lövhə məntiqi — bax CLAUDE.md bölmə 6 (Sifariş Axını)
 * və bölmə 7.1/7.2 (Panellər).
 */
final class SifarisService
{
    private const TIPLER = ['kurye', 'yukdasima'];
    private const ABUNE_AKTIV_ETIKETLERI = ['aktiv', 'pulsuz', 'pulsuz_qlobal'];
    private const SEKIL_ICAZE_VERILEN_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const SEKIL_MAX_HECM_BAYT = 5 * 1024 * 1024;

    private Sifaris $sifarisler;
    private Rayon $rayonlar;
    private YukdasimaOlcusu $yukdasimaOlculeri;
    private KuryeBolge $kuryeBolgeler;
    private DasiyiciOlcusu $dasiyiciOlculeri;
    private Kurye $kuryeler;
    private User $users;
    private LegalLog $legalLogs;
    private AbunelikService $abunelikService;
    private PushService $pushService;

    public function __construct()
    {
        $this->sifarisler = new Sifaris();
        $this->rayonlar = new Rayon();
        $this->yukdasimaOlculeri = new YukdasimaOlcusu();
        $this->kuryeBolgeler = new KuryeBolge();
        $this->dasiyiciOlculeri = new DasiyiciOlcusu();
        $this->kuryeler = new Kurye();
        $this->users = new User();
        $this->legalLogs = new LegalLog();
        $this->pushService = new PushService();
        $this->abunelikService = new AbunelikService();
    }

    /**
     * @throws ValidationException
     */
    public function yarat(array $input, int $musteriId): array
    {
        $tip = (string) ($input['tip'] ?? 'kurye');
        if (!in_array($tip, self::TIPLER, true)) {
            throw new ValidationException('Sifariş tipi düzgün seçilməyib.');
        }

        $gSehirId = (int) ($input['goturulme_sehir_id'] ?? 0);
        $gRayonId = (int) ($input['goturulme_rayon_id'] ?? 0);
        $gUnvan = trim((string) ($input['goturulme_unvan'] ?? ''));
        $cSehirId = (int) ($input['catdirilma_sehir_id'] ?? 0);
        $cRayonId = (int) ($input['catdirilma_rayon_id'] ?? 0);
        $cUnvan = trim((string) ($input['catdirilma_unvan'] ?? ''));
        $yukTesviri = trim((string) ($input['yuk_tesviri'] ?? ''));
        $teklifQiymet = $input['teklif_qiymet'] ?? null;
        $tecili = filter_var($input['tecili'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($gUnvan === '' || $cUnvan === '') {
            throw new ValidationException('Götürülmə və çatdırılma ünvanları mütləqdir.');
        }

        if (!$this->rayonlar->aktivSehirde($gRayonId, $gSehirId)) {
            throw new ValidationException('Götürülmə şəhəri/ərazisi düzgün deyil.');
        }
        if (!$this->rayonlar->aktivSehirde($cRayonId, $cSehirId)) {
            throw new ValidationException('Çatdırılma şəhəri/ərazisi düzgün deyil.');
        }

        if ($teklifQiymet !== null && $teklifQiymet !== '' && !is_numeric($teklifQiymet)) {
            throw new ValidationException('Təklif qiymət rəqəm olmalıdır.');
        }

        $yukdasimaOlcuId = null;
        if ($tip === 'yukdasima') {
            $yukdasimaOlcuId = (int) ($input['yukdasima_olcu_id'] ?? 0);
            if (!$this->yukdasimaOlculeri->exists($yukdasimaOlcuId)) {
                throw new ValidationException('Yükdaşıma ölçüsü düzgün seçilməyib.');
            }
        }

        $id = $this->sifarisler->create([
            'musteri_id' => $musteriId,
            'goturulme_sehir_id' => $gSehirId,
            'goturulme_rayon_id' => $gRayonId,
            'goturulme_unvan' => $gUnvan,
            'catdirilma_sehir_id' => $cSehirId,
            'catdirilma_rayon_id' => $cRayonId,
            'catdirilma_unvan' => $cUnvan,
            'yuk_tesviri' => $yukTesviri !== '' ? $yukTesviri : null,
            'teklif_qiymet' => ($teklifQiymet !== null && $teklifQiymet !== '') ? (float) $teklifQiymet : null,
            'tecili' => $tecili ? 1 : 0,
            'tip' => $tip,
            'yukdasima_olcu_id' => $yukdasimaOlcuId,
        ]);

        $this->yeniSifarisBildirisiGonder($gRayonId, $tip, $yukdasimaOlcuId, $gUnvan, $tecili);

        return $this->sifarisler->findById($id) ?? [];
    }

    /**
     * Yeni sifariş yaradılanda uyğun ərazidəki kuryer/yükdaşımalara push
     * bildirişi göndərir — SSE canlı lövhə yalnız tətbiq açıq olanda işləyir,
     * bu isə tətbiq bağlı olanda da xəbərdar edir. Onlayn/offline statusundan
     * asılı olmayaraq göndərilir (məqsəd elə budur — bağlı olan kuryeri işə
     * çağırmaq), yalnız aktiv abunə tələb olunur.
     */
    private function yeniSifarisBildirisiGonder(
        int $rayonId,
        string $tip,
        ?int $yukdasimaOlcuId,
        string $unvan,
        bool $tecili
    ): void {
        $namizedler = $this->kuryeler->rayonaVeTipeUygunlar($rayonId, $tip, $yukdasimaOlcuId);
        if ($namizedler === []) {
            return;
        }

        $baslik = $tecili ? 'Təcili yeni sifariş!' : 'Yeni sifariş!';
        $metn = 'Götürülmə: ' . $unvan;

        foreach ($namizedler as $namized) {
            if (!$this->kuryeAbunesiAktivdirmi($namized['id'])) {
                continue;
            }

            $this->pushService->gonder($namized['user_id'], $baslik, $metn, '/lovhe');
        }
    }

    /**
     * @throws ValidationException
     */
    public function legv(int $sifarisId, int $musteriId): void
    {
        if (!$this->sifarisler->cancel($sifarisId, $musteriId)) {
            throw new ValidationException(
                'Sifariş ləğv edilə bilmədi (mövcud deyil, sizə aid deyil və ya artıq bitib).'
            );
        }
    }

    /**
     * Müştəri tarixçəsi — daşıyıcı götürübsə (status='tamamlandi'), müştərinin
     * onunla əlaqə saxlaya bilməsi üçün ad + WhatsApp linki əlavə olunur (bax
     * SifarisService::gotur() — ayrıca "Tamamla" addımı yoxdur, götürmə=tamamlanma,
     * daşıyıcı məlumatı müştəri tərəfdə həmişəlik saxlanılır).
     */
    public function tarixce(int $musteriId): array
    {
        $sifarisler = $this->sifarisler->listByMusteri($musteriId);

        foreach ($sifarisler as &$s) {
            $kuryeAdi = trim(($s['kurye_ad'] ?? '') . ' ' . ($s['kurye_soyad'] ?? ''));
            $s['dasiyici_adi'] = $kuryeAdi !== '' ? $kuryeAdi : null;
            $s['dasiyici_whatsapp_link'] = !empty($s['kurye_whatsapp'])
                ? WhatsApp::link($s['kurye_whatsapp'], "Birlikdə sifarişi #{$s['id']} barədə əlaqə")
                : null;
        }
        unset($s);

        return $sifarisler;
    }

    /**
     * "Sifarişlərim" bölməsi — kuryerin/yükdaşımanın özünün götürdüyü sifarişlər,
     * müştəri ilə əlaqə saxlaya bilməsi üçün WhatsApp linki ilə birgə.
     */
    public function kuryeSifarisleri(int $kuryeId): array
    {
        $sifarisler = $this->sifarisler->listByKurye($kuryeId);

        foreach ($sifarisler as &$s) {
            $musteriAdi = trim(($s['musteri_ad'] ?? '') . ' ' . ($s['musteri_soyad'] ?? ''));
            $s['musteri_adi'] = $musteriAdi !== '' ? $musteriAdi : null;
            $s['musteri_whatsapp_link'] = !empty($s['musteri_whatsapp'])
                ? WhatsApp::link($s['musteri_whatsapp'], "Birlikdə sifarişi #{$s['id']} barədə əlaqə")
                : null;
        }
        unset($s);

        return $sifarisler;
    }

    /**
     * SSE lövhə üçün yeni sifarişlər — bax bölmə 5.3 (rayon filtri), 7.1.1 (tip/ölçü
     * filtri) və 7.2.1 ("Aktiv abunə olmalı; abunə bitibsə lövhə bağlıdır").
     */
    public function lovheYenileri(int $kuryeId, string $rol, int $lastId): array
    {
        $kurye = $this->kuryeler->findById($kuryeId);
        if ($kurye === null || !(bool) $kurye['onlayn']) {
            return [];
        }

        if (!$this->kuryeAbunesiAktivdirmi($kuryeId)) {
            return [];
        }

        $rayonIds = $this->kuryeBolgeler->getRayonIds($kuryeId);
        if ($rayonIds === []) {
            return [];
        }

        if ($rol === 'yukdasima') {
            $olcuIds = $this->dasiyiciOlculeri->olcuIdsByKurye($kuryeId);
            if ($olcuIds === []) {
                return [];
            }

            return $this->sifarisler->axtarisdaByRayonlarVeOlculer($rayonIds, $olcuIds, $lastId);
        }

        return $this->sifarisler->axtarisdaByRayonlar($rayonIds, $lastId);
    }

    /**
     * Atomic götürmə + WhatsApp link generasiyası (bax bölmə 6.3, 6.4).
     * "Uyğun daşıyıcı" qaydası (bölmə 7.1.1) burada da tətbiq olunur — SSE lövhə
     * filtri yalnız görünüşü idarə edir, təhlükəsizlik üçün server tərəfində də
     * yoxlanmalıdır.
     *
     * @throws ValidationException
     */
    public function gotur(int $sifarisId, int $kuryeId, string $rol, string $ip): array
    {
        $sifaris = $this->sifarisler->findById($sifarisId);
        if ($sifaris === null) {
            throw new ValidationException('Sifariş tapılmadı.');
        }

        if (!$this->kuryeAbunesiAktivdirmi($kuryeId)) {
            throw new ValidationException('Abunəniz aktiv deyil — sifariş götürə bilməzsiniz.');
        }

        if ($sifaris['tip'] !== $rol) {
            throw new ValidationException('Bu sifariş sizin xidmət tipinizə uyğun deyil.');
        }

        if ($rol === 'yukdasima') {
            $olcuIds = $this->dasiyiciOlculeri->olcuIdsByKurye($kuryeId);
            if (!in_array((int) $sifaris['yukdasima_olcu_id'], $olcuIds, true)) {
                throw new ValidationException('Bu sifarişin ölçüsü sizin daşıya bildiyiniz ölçülərə uyğun deyil.');
            }
        }

        if (!$this->sifarisler->atomicGotur($sifarisId, $kuryeId)) {
            throw new ValidationException('Sifariş artıq götürülüb.');
        }

        $this->kuryeler->incrementTamamlanan($kuryeId);

        $kurye = $this->kuryeler->findById($kuryeId);
        $kuryeUser = $kurye !== null ? $this->users->findById((int) $kurye['user_id']) : null;
        $musteri = $this->users->findById((int) $sifaris['musteri_id']);

        $this->legalLogs->yaz(
            $kuryeUser['id'] ?? null,
            $sifarisId,
            'sifaris_goturuldu',
            ['kurye_id' => $kuryeId],
            $ip
        );

        $metn = "Birlikdə sifarişi #{$sifarisId} barədə əlaqə";

        if ($musteri !== null) {
            $this->pushService->gonder(
                (int) $musteri['id'],
                'Daşıyıcı tapıldı!',
                trim(($kuryeUser['ad'] ?? '') . ' qəbul etdi')
            );
        }

        return [
            'sifaris_id' => $sifarisId,
            'musteri_adi' => trim(($musteri['ad'] ?? '') . ' ' . ($musteri['soyad'] ?? '')),
            'musteri_whatsapp_link' => $musteri !== null ? WhatsApp::link($musteri['whatsapp'], $metn) : null,
            'kurye_whatsapp_link' => $kuryeUser !== null ? WhatsApp::link($kuryeUser['whatsapp'], $metn) : null,
            'kurye_adi' => $kuryeUser['ad'] ?? null,
            'neqliyyat' => $kurye['neqliyyat'] ?? null,
            'goturulme_unvan' => $sifaris['goturulme_unvan'],
            'catdirilma_unvan' => $sifaris['catdirilma_unvan'],
        ];
    }

    public function onlaynToggle(int $kuryeId, bool $onlayn): void
    {
        $this->kuryeler->setOnlayn($kuryeId, $onlayn);
    }

    /**
     * Kuryer/yükdaşıma profil şəkli yükləmə — bax profil.php "sosial-şəbəkə
     * stilli" bölmə tələbi. Şəkil storage/kurye-sekiller/-da saxlanılır, DB-də
     * yalnız fayl adı (BannerService-dəki yükləmə qaydasına bənzər).
     *
     * @param array{name:string, type:string, tmp_name:string, error:int, size:int}|null $file
     * @throws ValidationException
     */
    public function sekilYukle(int $kuryeId, ?array $file, string $storageDir): string
    {
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('Şəkil yüklənmədi.');
        }
        if ($file['size'] > self::SEKIL_MAX_HECM_BAYT) {
            throw new ValidationException('Şəkil ölçüsü 5 MB-dan çox ola bilməz.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('Şəkil yükləmə etibarsızdır.');
        }

        $mime = (string) mime_content_type($file['tmp_name']);
        if (!array_key_exists($mime, self::SEKIL_ICAZE_VERILEN_MIME)) {
            throw new ValidationException('Yalnız JPEG, PNG və ya WEBP formatına icazə verilir.');
        }
        if (@getimagesize($file['tmp_name']) === false) {
            throw new ValidationException('Fayl həqiqi şəkil deyil.');
        }

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $adFayl = bin2hex(random_bytes(16)) . '.' . self::SEKIL_ICAZE_VERILEN_MIME[$mime];
        $hedefYol = rtrim($storageDir, '/') . '/' . $adFayl;

        if (!move_uploaded_file($file['tmp_name'], $hedefYol)) {
            throw new ValidationException('Şəkil saxlanıla bilmədi.');
        }

        $this->kuryeler->setSekil($kuryeId, $adFayl);

        return $adFayl;
    }

    /**
     * Kuryerin öz seçdiyi ərazilər — profil səhifəsi üçün (bax bölmə 7.2.2).
     */
    public function kuryeBolgeleri(int $kuryeId): array
    {
        return $this->kuryeBolgeler->getRayonlar($kuryeId);
    }

    /**
     * @param int[] $rayonIds
     * @throws ValidationException
     */
    public function bolgelerYenile(int $kuryeId, array $rayonIds): void
    {
        $rayonIds = array_values(array_unique(array_map('intval', $rayonIds)));
        $movcud = $this->rayonlar->existingActiveIds($rayonIds);

        if (count($movcud) !== count($rayonIds)) {
            throw new ValidationException('Seçilmiş ərazilərdən bəziləri mövcud deyil.');
        }

        $this->kuryeBolgeler->sync($kuryeId, $rayonIds);
    }

    public function passivlesdirKohneleri(): int
    {
        return $this->sifarisler->passivlesdirKohneleri();
    }

    /**
     * Bax bölmə 7.2.1: "Aktiv abunə (pulsuz və ya pullu) olmalı; abunə bitibsə
     * lövhə bağlıdır." Qlobal abunə rejimi dayandırılıbsa da kuryer işləyə bilir.
     */
    private function kuryeAbunesiAktivdirmi(int $kuryeId): bool
    {
        try {
            $status = $this->abunelikService->status($kuryeId);
        } catch (ValidationException) {
            return false;
        }

        return in_array($status['label'], self::ABUNE_AKTIV_ETIKETLERI, true);
    }
}
