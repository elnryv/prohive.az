<?php
declare(strict_types=1);

/**
 * Foto/video qəbulu və təhlükəsizliyi (bölmə 12.2).
 * - MIME finfo ilə yoxlanır, uzantıya güvənilmir.
 * - Şəkillər GD ilə yenidən render olunur (EXIF/metadata təmizlənir) → WebP.
 * - Fayl adı: random hash, orijinal ad saxlanmır.
 *
 * v1 sadələşdirməsi: house_photos sxemində tək filename sütunu olduğu üçün
 * (ayrı thumb/cover variant sütunu yoxdur) hər foto TƏK WebP kimi 1600px-ə
 * qədər saxlanılır və cover/qalereya/thumb kontekstlərinin hamısında CSS
 * səviyyəsində (object-fit) uyğunlaşdırılır.
 */
final class Upload
{
    private const MAX_IMAGE_BYTES = 20 * 1024 * 1024;
    private const MAX_VIDEO_BYTES = 30 * 1024 * 1024;
    private const MAX_DIMENSION = 1600;
    private const QUALITY = 80;
    private const ALLOWED_IMAGE_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /** @param array{tmp_name:string,error:int,size:int} $file */
    public static function storeImage(array $file, int $houseId): string
    {
        self::assertUploadOk($file);

        if ((int) $file['size'] > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Şəkil 20MB-dan böyükdür');
        }

        $mime = self::detectMime($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_IMAGE_MIME, true)) {
            throw new RuntimeException('Dəstəklənməyən şəkil formatı (yalnız JPEG/PNG/WebP)');
        }

        $src = self::createImageFromFile($file['tmp_name'], $mime);
        $src = self::resizeIfNeeded($src, self::MAX_DIMENSION);

        $dir = self::houseDir($houseId);
        $filename = bin2hex(random_bytes(8)) . '.webp';
        imagewebp($src, $dir . '/' . $filename, self::QUALITY);
        imagedestroy($src);

        return $filename;
    }

    /** @param array{tmp_name:string,error:int,size:int} $file */
    public static function storeVideo(array $file, int $houseId): string
    {
        self::assertUploadOk($file);

        if ((int) $file['size'] > self::MAX_VIDEO_BYTES) {
            throw new RuntimeException('Video 30MB-dan böyükdür');
        }

        $mime = self::detectMime($file['tmp_name']);
        if ($mime !== 'video/mp4') {
            throw new RuntimeException('Yalnız MP4 video qəbul olunur');
        }

        $dir = self::houseDir($houseId);
        $filename = bin2hex(random_bytes(8)) . '.mp4';
        $dest = $dir . '/' . $filename;

        $ffmpeg = self::findFfmpeg();
        $reencoded = false;
        if ($ffmpeg !== null) {
            $cmd = sprintf(
                '%s -y -i %s -vf %s -c:v libx264 -preset fast -crf 23 -c:a aac -movflags +faststart %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($file['tmp_name']),
                escapeshellarg('scale=-2:720'),
                escapeshellarg($dest)
            );
            exec($cmd, $unusedOutput, $code);
            $reencoded = $code === 0 && is_file($dest);
        }

        if (!$reencoded) {
            if (!move_uploaded_file($file['tmp_name'], $dest) && !copy($file['tmp_name'], $dest)) {
                throw new RuntimeException('Video saxlanıla bilmədi');
            }
        }

        return $filename;
    }

    public static function deleteFile(int $houseId, string $filename): void
    {
        $path = APP_ROOT . '/storage/uploads/houses/' . $houseId . '/' . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function resizeIfNeeded(\GdImage $src, int $maxDimension): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if (max($w, $h) <= $maxDimension) {
            return $src;
        }

        $ratio = $maxDimension / max($w, $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    public static function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? finfo_file($finfo, $path) : false;
        if ($finfo !== false) {
            finfo_close($finfo);
        }
        return $mime !== false ? $mime : 'application/octet-stream';
    }

    private static function createImageFromFile(string $path, string $mime): \GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if (!($image instanceof \GdImage)) {
            throw new RuntimeException('Şəkil oxuna bilmədi (zədəli fayl ola bilər)');
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);
        return $image;
    }

    private static function houseDir(int $houseId): string
    {
        $dir = APP_ROOT . '/storage/uploads/houses/' . $houseId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** @param array{tmp_name?:string,error?:int} $file */
    private static function assertUploadOk(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Fayl yüklənmədi');
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('Etibarsız fayl mənbəyi');
        }
    }

    private static function findFfmpeg(): ?string
    {
        $path = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
        return $path !== '' ? $path : null;
    }
}
