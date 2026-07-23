<?php
declare(strict_types=1);

// Hissə 4.5/12 — OG kart generatoru: brend fon + marşrut + yük növü + tarix,
// GD ilə yaradılır və statik fayl kimi keşlənir (bir dəfə generasiya, sonra
// birbaşa fayldan xidmət olunur — bax public/og/generate.php).

const OG_WIDTH = 1200;
const OG_HEIGHT = 630;
const OG_FONT_REGULAR = __DIR__ . '/fonts/Inter-Regular.ttf';
const OG_FONT_BOLD = __DIR__ . '/fonts/Inter-Bold.ttf';

function og_hex_color($im, string $hex): int
{
    $hex = ltrim($hex, '#');
    return imagecolorallocate(
        $im,
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2))
    );
}

// Verilmiş enə sığması üçün mətni sözlərə görə sətirlərə bölür.
function og_wrap_text(string $text, string $font, float $size, int $maxWidth): array
{
    $words = preg_split('/\s+/u', $text) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $candidate = $current === '' ? $word : "{$current} {$word}";
        $box = imagettfbbox($size, 0, $font, $candidate);
        $width = $box[2] - $box[0];
        if ($width > $maxWidth && $current !== '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $candidate;
        }
    }
    if ($current !== '') {
        $lines[] = $current;
    }

    return $lines;
}

function generate_og_image(array $order, string $cargoTypeName, string $outputPath): void
{
    $im = imagecreatetruecolor(OG_WIDTH, OG_HEIGHT);

    // Brend fon: primary rənginin şaquli qradienti (Hissə 2.1 --primary → --primary-light).
    $topR = 37; $topG = 99; $topB = 235;
    $botR = 59; $botG = 130; $botB = 246;
    for ($y = 0; $y < OG_HEIGHT; $y++) {
        $ratio = $y / OG_HEIGHT;
        $r = (int) ($topR + ($botR - $topR) * $ratio);
        $g = (int) ($topG + ($botG - $topG) * $ratio);
        $b = (int) ($topB + ($botB - $topB) * $ratio);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, OG_WIDTH, $y, $color);
    }

    $white = imagecolorallocate($im, 255, 255, 255);
    $whiteSoft = imagecolorallocatealpha($im, 255, 255, 255, 40);

    // Sayt adı (yuxarı sol küncdə brendinq)
    imagettftext($im, 22, 0, 64, 80, $whiteSoft, OG_FONT_BOLD, 'YÜK.BİRLİKDƏ.BİZ');

    // Yük növü çipi
    imagettftext($im, 20, 0, 64, 220, $whiteSoft, OG_FONT_REGULAR, mb_strtoupper($cargoTypeName));

    // Marşrut — "haradan" və "haraya" ayrı sətirlərdə, aralarında əl ilə
    // çəkilmiş ox (font şəklindən asılı olmayaraq — "→" işarəsi Inter-in heç
    // bir subset-ində mövcud deyil, GD-nin öz tofu qutusunu göstərməsinin
    // qarşısı belə alınır).
    $fromLabel = $order['from_city'] . (!empty($order['from_district']) ? ', ' . $order['from_district'] : '');
    $toLabel = $order['to_city'] . (!empty($order['to_district']) ? ', ' . $order['to_district'] : '');

    $fromLines = og_wrap_text($fromLabel, OG_FONT_BOLD, 46, OG_WIDTH - 128);
    $toLines = og_wrap_text($toLabel, OG_FONT_BOLD, 46, OG_WIDTH - 128);
    $lineHeight = 58;

    $cursorY = 300;
    foreach ($fromLines as $line) {
        imagettftext($im, 46, 0, 64, $cursorY, $white, OG_FONT_BOLD, $line);
        $cursorY += $lineHeight;
    }

    $arrowX = 64 + 14;
    $arrowTopY = $cursorY + 6;
    imagefilledpolygon($im, [
        $arrowX - 14, $arrowTopY,
        $arrowX + 14, $arrowTopY,
        $arrowX, $arrowTopY + 24,
    ], $whiteSoft);
    $cursorY += 74;

    foreach ($toLines as $line) {
        imagettftext($im, 46, 0, 64, $cursorY, $white, OG_FONT_BOLD, $line);
        $cursorY += $lineHeight;
    }

    // Tarix/saat
    $whenLabel = date('d.m.Y, H:i', strtotime($order['date_time']));
    imagettftext($im, 26, 0, 64, $cursorY + 30, $white, OG_FONT_REGULAR, $whenLabel);

    // Elan nömrəsi (alt sağ küncdə)
    $numberText = $order['number'] ?? '';
    $box = imagettfbbox(20, 0, OG_FONT_REGULAR, $numberText);
    $numberWidth = $box[2] - $box[0];
    imagettftext($im, 20, 0, OG_WIDTH - 64 - $numberWidth, OG_HEIGHT - 48, $whiteSoft, OG_FONT_REGULAR, $numberText);

    imagepng($im, $outputPath);
    imagedestroy($im);
}
