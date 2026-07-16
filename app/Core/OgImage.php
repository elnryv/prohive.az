<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Paylaşım kartı (OG image) generatoru — GD ilə, yalnız daxili çağırışla
 * işə düşür (bölmə 6.8, 12.1: "OG şəkil generatoru yalnız daxili çağırışla").
 * Tünd fon + narıncı marşrut oxu, kateqoriya ikonu, loqo.
 */
final class OgImage
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    private static function fontPath(bool $bold = false): string
    {
        $dir = dirname(__DIR__, 2) . '/public/assets/fonts';
        return $bold ? "{$dir}/DejaVuSans-Bold.ttf" : "{$dir}/DejaVuSans.ttf";
    }

    public static function render(string $fromName, string $toName, string $categoryIcon, string $categoryName): string
    {
        $im = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $coal = imagecolorallocate($im, 0x13, 0x13, 0x15);
        $coal2 = imagecolorallocate($im, 0x1B, 0x1B, 0x1E);
        $amber = imagecolorallocate($im, 0xE8, 0x86, 0x2E);
        $txt = imagecolorallocate($im, 0xF2, 0xF0, 0xEC);
        $txtSoft = imagecolorallocate($im, 0x9C, 0x9A, 0x94);

        imagefilledrectangle($im, 0, 0, self::WIDTH, self::HEIGHT, $coal);
        imagefilledrectangle($im, 0, self::HEIGHT - 140, self::WIDTH, self::HEIGHT, $coal2);

        // Loqo
        imagettftext($im, 22, 0, 60, 80, $amber, self::fontPath(true), 'BİRLİKDƏ YÜK');

        // Kateqoriya (emoji GD/freetype ilə rəngli göstərilmədiyi üçün OG şəklində yalnız mətn istifadə olunur)
        imagettftext($im, 26, 0, 60, 190, $txtSoft, self::fontPath(), mb_strtoupper($categoryName));

        // Marşrut (əsas element)
        $routeText = self::fitText($fromName, 22) . '  →  ' . self::fitText($toName, 22);
        self::drawFittedRoute($im, $routeText, $txt, $amber);

        // Alt zolaq: brend
        imagettftext($im, 20, 0, 60, self::HEIGHT - 55, $txtSoft, self::fontPath(), 'yuk.birlikde.biz');

        ob_start();
        imagepng($im);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    private static function fitText(string $text, int $maxChars): string
    {
        $text = mb_convert_case($text, MB_CASE_TITLE);
        return mb_strlen($text) > $maxChars ? mb_substr($text, 0, $maxChars - 1) . '…' : $text;
    }

    private static function drawFittedRoute($im, string $text, int $txtColor, int $amberColor): void
    {
        $size = 48;
        $font = self::fontPath(true);
        while ($size > 24) {
            $box = imagettfbbox($size, 0, $font, $text);
            $width = $box[2] - $box[0];
            if ($width <= self::WIDTH - 120) {
                break;
            }
            $size -= 2;
        }
        // Ox işarəsini amber rənglə vurğulamaq üçün mətn ayrıca hissələrə bölünür
        $parts = explode('  →  ', $text, 2);
        $y = 300;
        $x = 60;
        if (count($parts) === 2) {
            imagettftext($im, $size, 0, $x, $y, $txtColor, $font, $parts[0]);
            $box = imagettfbbox($size, 0, $font, $parts[0] . '  ');
            $x2 = $x + ($box[2] - $box[0]);
            imagettftext($im, $size, 0, $x2, $y, $amberColor, $font, '→');
            $box2 = imagettfbbox($size, 0, $font, '→  ');
            $x3 = $x2 + ($box2[2] - $box2[0]);
            imagettftext($im, $size, 0, $x3, $y, $txtColor, $font, $parts[1]);
        } else {
            imagettftext($im, $size, 0, $x, $y, $txtColor, $font, $text);
        }
    }

    public static function saveForListing(string $publicCode, string $fromName, string $toName, string $categoryIcon, string $categoryName): void
    {
        $png = self::render($fromName, $toName, $categoryIcon, $categoryName);
        $dir = Config::get('upload.og_image_dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("{$dir}/{$publicCode}.png", $png);
    }
}
