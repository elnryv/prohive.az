<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Statik faylların (CSS/JS) keş-sındırma versiyası — fayl dəyişən kimi
 * `filemtime()` avtomatik dəyişir, brauzerin köhnə keşlənmiş nüsxəni
 * saxlamasının qarşısını alır (məs. admin.js-ə yeni funksiya əlavə olunanda
 * köhnə keşlənmiş nüsxə həmin funksiyanı tapmayıb bütün skripti dayandıra
 * bilərdi — bax Çıxış düyməsi bug-ı). Sərt versiya nömrəsi əl ilə saxlamağa
 * ehtiyac yoxdur, həmişə düzgündür.
 */
final class Asset
{
    public static function v(string $relPath): string
    {
        $full = dirname(__DIR__, 2) . '/public/assets/' . $relPath;
        $mtime = @filemtime($full);

        return '/assets/' . $relPath . ($mtime !== false ? '?v=' . $mtime : '');
    }
}
