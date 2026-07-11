<?php

declare(strict_types=1);

/**
 * CLI: php database/generate_pwa_icons.php
 * `public/assets/icons/icon-192.png` və `icon-512.png` yaradır (bax
 * PROGRESS.md "Qeydlər" — əvvəlki versiya transparent künclü dairə idi,
 * bu "maskable" tələbini pozurdu: OS kvadrat/dairə maska tətbiq edəndə
 * şəffaf küncdən "deşik" görünə bilərdi). Yeni versiya tam-bleed qara
 * kvadrat fon + ağ "B" işarəsi, maskable safe-zone (mərkəzi ~%80 dairə)
 * daxilində. GD ilə 8x supersample çəkilir, sonra hamar kənar üçün
 * `imagecopyresampled` ilə əsl ölçüyə endirilir.
 */

function drawIcon(int $size): \GdImage
{
    $scale = 8;
    $big = $size * $scale;

    $im = imagecreatetruecolor($big, $big);
    imagesavealpha($im, false);

    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $big - 1, $big - 1, $black);

    $white = imagecolorallocate($im, 255, 255, 255);

    $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    $fontSize = (int) round($big * 0.5);

    // "B" hərfini mərkəzləmək üçün bounding box hesablanır (imagettftext
    // baseline-dan çəkir, ona görə mərkəz düzəlişi lazımdır).
    $bbox = imagettfbbox($fontSize, 0, $fontPath, 'B');
    $textWidth = abs($bbox[4] - $bbox[0]);
    $textHeight = abs($bbox[5] - $bbox[1]);
    $x = (int) round(($big - $textWidth) / 2) - $bbox[0];
    $y = (int) round(($big - $textHeight) / 2) - $bbox[5];

    imagettftext($im, $fontSize, 0, $x, $y, $white, $fontPath, 'B');

    $out = imagecreatetruecolor($size, $size);
    imagesavealpha($out, false);
    imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, $big, $big);
    imagedestroy($im);

    return $out;
}

$outDir = dirname(__DIR__) . '/public/assets/icons';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

foreach ([192, 512] as $size) {
    $icon = drawIcon($size);
    $path = $outDir . "/icon-{$size}.png";
    imagepng($icon, $path, 9);
    imagedestroy($icon);
    echo "Yaradıldı: {$path}\n";
}

echo "\nQeyd: bu, hələ də proqramla çəkilmiş sadə plaseholder-dir (marka\n";
echo "dizaynı deyil). Real loqo hazır olanda bu skripti və ya faylları\n";
echo "birbaşa əvəzləyin — manifest.json/head.php dəyişməyəcək.\n";
