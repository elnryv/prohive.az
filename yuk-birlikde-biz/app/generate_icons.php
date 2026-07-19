<?php
declare(strict_types=1);

// Bir dəfəlik icon generasiyası (GD). İşlətmə: php app/generate_icons.php
// Nəticə: public/assets/icons/icon-192.png və icon-512.png (maskable-safe).

function draw_icon(int $size, string $path): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $primary = imagecolorallocate($img, 0x25, 0x63, 0xEB);
    imagefill($img, 0, 0, $primary);

    // Maskable safe-zone (~80%) daxilində sadə yük qutusu qlifi (ağ, dəyirmi künclü).
    $white = imagecolorallocate($img, 255, 255, 255);
    $half = $size * 0.28;
    $x1 = (int) ($size / 2 - $half);
    $y1 = (int) ($size / 2 - $half);
    $x2 = (int) ($size / 2 + $half);
    $y2 = (int) ($size / 2 + $half);
    $radius = (int) ($size * 0.08);

    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $white);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $white);
    foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$cx, $cy]) {
        imagefilledellipse($img, $cx, $cy, $radius * 2, $radius * 2, $white);
    }

    // "tape" zolağı — qutunun ortasından üfüqi
    $tapeH = (int) ($size * 0.045);
    imagefilledrectangle($img, $x1, (int) ($size / 2 - $tapeH / 2), $x2, (int) ($size / 2 + $tapeH / 2), $primary);

    imagepng($img, $path);
    imagedestroy($img);
}

$dir = __DIR__ . '/../public/assets/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

draw_icon(192, $dir . '/icon-192.png');
draw_icon(512, $dir . '/icon-512.png');
draw_icon(180, $dir . '/apple-touch-icon.png');
draw_icon(32, $dir . '/favicon-32.png');

echo "İkonlar yaradıldı: {$dir}\n";
