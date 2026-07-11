<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Models\Banner;

/**
 * Reklam banner sistemi — bax bölmə 8.4. Şəkillər storage/banners/-da saxlanır,
 * DB-də yalnız yol qeyd olunur (BANNER-8.4). Görüntüləmə üçün bax bölmə 6.4-ə
 * bənzər şəkildə əlavə olunan GET /banner-sekil/{fayl} marşrutu (routes/web.php).
 */
final class BannerService
{
    private const HEDEFLER = ['hamisi', 'musteri', 'kurye'];
    private const ICAZE_VERILEN_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    private const MAX_HECM_BAYT = 5 * 1024 * 1024;
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    private Banner $bannerler;

    public function __construct()
    {
        $this->bannerler = new Banner();
    }

    /**
     * @param array{name:string, type:string, tmp_name:string, error:int, size:int}|null $file
     * @throws ValidationException
     */
    public function yarat(array $input, ?array $file, string $storageDir): array
    {
        $baslik = trim((string) ($input['baslik'] ?? ''));
        $link = trim((string) ($input['link'] ?? ''));
        $hedef = (string) ($input['hedef'] ?? 'hamisi');
        $baslama = (string) ($input['baslama'] ?? '');
        $bitme = (string) ($input['bitme'] ?? '');
        $sira = (int) ($input['sira'] ?? 0);

        if (!in_array($hedef, self::HEDEFLER, true)) {
            throw new ValidationException('Hədəf "hamisi", "musteri" və ya "kurye" olmalıdır.');
        }
        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException('Link düzgün URL formatında deyil.');
        }

        $baslamaTarix = \DateTimeImmutable::createFromFormat('Y-m-d', $baslama);
        $bitmeTarix = \DateTimeImmutable::createFromFormat('Y-m-d', $bitme);
        if ($baslamaTarix === false || $bitmeTarix === false) {
            throw new ValidationException('Başlama/bitmə tarixi Y-m-d formatında olmalıdır.');
        }
        if ($bitmeTarix < $baslamaTarix) {
            throw new ValidationException('Bitmə tarixi başlama tarixindən əvvəl ola bilməz.');
        }

        $sekilYol = $this->sekiliYukle($file, $storageDir);

        $id = $this->bannerler->create([
            'baslik' => $baslik !== '' ? $baslik : null,
            'sekil_yol' => $sekilYol,
            'link' => $link !== '' ? $link : null,
            'hedef' => $hedef,
            'baslama' => $baslamaTarix->format('Y-m-d'),
            'bitme' => $bitmeTarix->format('Y-m-d'),
            'sira' => $sira,
        ]);

        return $this->bannerler->findById($id) ?? [];
    }

    /**
     * @param array{name:string, type:string, tmp_name:string, error:int, size:int}|null $file
     * @throws ValidationException
     */
    private function sekiliYukle(?array $file, string $storageDir): string
    {
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('Banner şəkli yüklənmədi.');
        }
        if ($file['size'] > self::MAX_HECM_BAYT) {
            throw new ValidationException('Şəkil ölçüsü 5 MB-dan çox ola bilməz.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('Şəkil yükləmə etibarsızdır.');
        }

        $mime = (string) mime_content_type($file['tmp_name']);
        if (!array_key_exists($mime, self::ICAZE_VERILEN_MIME)) {
            throw new ValidationException('Yalnız JPEG, PNG və ya WEBP formatına icazə verilir.');
        }
        if (@getimagesize($file['tmp_name']) === false) {
            throw new ValidationException('Fayl həqiqi şəkil deyil.');
        }

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $adFayl = bin2hex(random_bytes(16)) . '.' . self::ICAZE_VERILEN_MIME[$mime];
        $hedefYol = rtrim($storageDir, '/') . '/' . $adFayl;

        if (!move_uploaded_file($file['tmp_name'], $hedefYol)) {
            throw new ValidationException('Şəkil saxlanıla bilmədi.');
        }

        return $adFayl;
    }

    public function siyahi(int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = min(self::MAX_LIMIT, max(1, $limit ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $data = $this->bannerler->listAll($limit, $offset);
        $total = $this->bannerler->countAll();

        return [
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function aktivlikDeyis(int $id, bool $aktiv): void
    {
        if ($this->bannerler->findById($id) === null) {
            throw new ValidationException('Banner tapılmadı.');
        }

        $this->bannerler->setAktiv($id, $aktiv);
    }
}
