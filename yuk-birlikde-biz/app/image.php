<?php
declare(strict_types=1);

// Hissə 11.1: MIME + real məzmun yoxlanışı, GD ilə yenidən enkodlama (metadata
// təmizlənir), təsadüfi fayl adları, maks 5MB.
function save_uploaded_avatar(array $file, string $storageDir): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('UPLOAD_FAILED');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('FILE_TOO_LARGE');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $image = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png' => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default => null,
    };

    if ($image === null) {
        throw new RuntimeException('UNSUPPORTED_FORMAT');
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $side = min($width, $height);
    $srcX = (int) (($width - $side) / 2);
    $srcY = (int) (($height - $side) / 2);

    $target = 480;
    $square = imagecreatetruecolor($target, $target);
    imagecopyresampled($square, $image, 0, 0, $srcX, $srcY, $target, $target, $side, $side);
    imagedestroy($image);

    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    imagejpeg($square, $storageDir . '/' . $filename, 85);
    imagedestroy($square);

    return $filename;
}
