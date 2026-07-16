<?php
declare(strict_types=1);

/**
 * Birlikdə Getdik PWA ikonları — real loqo mövcud olmadığı üçün dizayn
 * palitrasına (bölmə 10.1) uyğun sadə monoqram+ev silueti generasiya edir.
 */

function drawIcon(int $size, bool $maskable): \GdImage
{
    $im = imagecreatetruecolor($size, $size);
    imagesavealpha($im, true);
    imagealphablending($im, true);

    $pine = imagecolorallocate($im, 0x14, 0x33, 0x2A);
    $leaf = imagecolorallocate($im, 0x3E, 0x7C, 0x4F);
    $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

    // fon: yaşıl gradient (pine -> leaf)
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = (int) (0x14 + (0x3E - 0x14) * $ratio);
        $g = (int) (0x33 + (0x7C - 0x33) * $ratio);
        $b = (int) (0x2A + (0x4F - 0x2A) * $ratio);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $size, $y, $color);
    }

    // maskable ikon üçün təhlükəsiz zona (mərkəzdən ~40% radius) daxilində saxlanılır
    $scale = $maskable ? 0.5 : 0.66;
    $cx = $size / 2;
    $cy = $size / 2;
    $h = $size * $scale;
    $w = $h * 0.9;

    // sadə "ev" silueti: üçbucaq dam + kvadrat gövdə
    $roofTop = $cy - $h / 2;
    $bodyTop = $cy - $h / 6;
    $bodyBottom = $cy + $h / 2;
    $left = $cx - $w / 2;
    $right = $cx + $w / 2;

    $roof = [
        (int) $cx, (int) $roofTop,
        (int) ($left - $w * 0.08), (int) $bodyTop,
        (int) ($right + $w * 0.08), (int) $bodyTop,
    ];
    imagefilledpolygon($im, $roof, $white);
    imagefilledrectangle($im, (int) $left, (int) $bodyTop, (int) $right, (int) $bodyBottom, $white);

    // qapı (yaşıl kəsim)
    $doorW = $w * 0.22;
    $doorH = ($bodyBottom - $bodyTop) * 0.55;
    imagefilledrectangle(
        $im,
        (int) ($cx - $doorW / 2),
        (int) ($bodyBottom - $doorH),
        (int) ($cx + $doorW / 2),
        (int) $bodyBottom,
        $leaf
    );

    return $im;
}

$outDir = '/home/user/prohive.az/public/assets/icons';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$icon192 = drawIcon(192, false);
imagepng($icon192, $outDir . '/icon-192.png');
imagedestroy($icon192);

$icon512 = drawIcon(512, false);
imagepng($icon512, $outDir . '/icon-512.png');
imagedestroy($icon512);

$maskable = drawIcon(512, true);
imagepng($maskable, $outDir . '/maskable-512.png');
imagedestroy($maskable);

echo "İkonlar yaradıldı: icon-192.png, icon-512.png, maskable-512.png\n";
