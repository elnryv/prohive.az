<?php

declare(strict_types=1);

// İstifadəçinin göndərdiyi hazır loqo şəklini (B+yük maşını + "Birlikdə Yük" wordmark,
// ağ dəyirmi-künclü kart üzərində) PWA/ana-ekran ikonları üçün lazımi ölçülərə salır.
// İşlətmək: php scripts/apply_logo.php

$src = __DIR__ . '/assets_src/logo_source.png';
$dir = __DIR__ . '/../public/assets/icons';

function resizeTo(string $srcPath, string $destPath, int $size): void
{
    $srcImg = imagecreatefrompng($srcPath);
    $srcW = imagesx($srcImg);
    $srcH = imagesy($srcImg);

    $dest = imagecreatetruecolor($size, $size);
    imagesavealpha($dest, true);
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefill($dest, 0, 0, $transparent);

    imagecopyresampled($dest, $srcImg, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
    imagepng($dest, $destPath);
    imagedestroy($dest);
    imagedestroy($srcImg);
    echo "Yazıldı: $destPath ({$size}x{$size})\n";
}

resizeTo($src, $dir . '/icon-512.png', 512);
resizeTo($src, $dir . '/icon-192.png', 192);
resizeTo($src, $dir . '/badge-72.png', 72);
