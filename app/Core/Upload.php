<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Şəkil yükləmə: real məzmun yoxlanışı (finfo + getimagesize) və GD ilə
 * WebP-ə yenidən kodlama — orijinal bayt axını heç vaxt diskə yazılmır,
 * beləliklə upload-a PHP/skript keçməsi mümkün deyil (12.1).
 */
final class Upload
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array{ok:bool, filename?:string, error?:string}
     */
    public static function storeImage(array $file, string $destDir, int $maxBytes, int $maxDimension = 1600): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'upload_failed'];
        }
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'too_large'];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'invalid_upload'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'invalid_type'];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['ok' => false, 'error' => 'invalid_image'];
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default => false,
        };
        if ($image === false) {
            return ['ok' => false, 'error' => 'decode_failed'];
        }

        [$width, $height] = [imagesx($image), imagesy($image)];
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.webp';
        $fullPath = rtrim($destDir, '/') . '/' . $filename;
        $saved = imagewebp($image, $fullPath, 82);
        imagedestroy($image);

        if (!$saved) {
            return ['ok' => false, 'error' => 'save_failed'];
        }

        return ['ok' => true, 'filename' => $filename];
    }
}
