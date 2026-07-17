<?php

declare(strict_types=1);

// Birlikdə Yük — PWA/ana-ekran ikonlarını yeni mavi brend rənginə uyğun
// proqramatik yaradır (dizayn fayl əvəzinə, kənar alət tələb etmədən).
// İşlətmək: php scripts/gen_icons.php

function drawTruckGlyph(\GdImage $img, int $size, int $cx, int $cy, int $white): void
{
    $s = $size / 24; // 24x24 vahid koordinat sistemi, Icon.php-dəki 'truck' ikonuna bənzər
    // Fiqurun faktiki bounding-box mərkəzi (x:1-21, y:3-17.3) — 12/9 kimi "dəyirmi" dəyərlər
    // deyil, düzgün optik mərkəzləmə üçün bu dəqiq median nöqtələr istifadə olunur.
    $ox = $cx - 11 * $s;
    $oy = $cy - 10.15 * $s;

    $px = static fn (float $x): int => (int) round($ox + $x * $s);
    $py = static fn (float $y): int => (int) round($oy + $y * $s);

    // Kabin (böyük düzbucaq gövdə)
    imagefilledrectangle($img, $px(1), $py(3), $px(14), $py(13), $white);
    // Mühərrik/kabin hissəsi (sağda kiçik trapesiya -> sadəcə düzbucaq)
    imagefilledrectangle($img, $px(14), $py(8), $px(21), $py(13), $white);

    // Təkərlər
    $wheelR = (int) round(3.6 * $s);
    imagefilledellipse($img, $px(5.5), $py(15.5), $wheelR, $wheelR, $white);
    imagefilledellipse($img, $px(17.5), $py(15.5), $wheelR, $wheelR, $white);
}

function makeIcon(int $size, string $path, bool $withPadding): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $bg = imagecolorallocate($img, 0x2F, 0x6F, 0xED);
    imagefill($img, 0, 0, $bg);

    $white = imagecolorallocate($img, 255, 255, 255);
    $glyphSize = $withPadding ? (int) round($size * 0.52) : (int) round($size * 0.62);
    drawTruckGlyph($img, $glyphSize, (int) round($size / 2), (int) round($size / 2) + (int) round($size * 0.02), $white);

    imagepng($img, $path);
    imagedestroy($img);
    echo "Yazıldı: $path ({$size}x{$size})\n";
}

$dir = __DIR__ . '/../public/assets/icons';
makeIcon(512, $dir . '/icon-512.png', true);
makeIcon(192, $dir . '/icon-192.png', true);
makeIcon(72, $dir . '/badge-72.png', true);
