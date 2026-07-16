<?php

declare(strict_types=1);

namespace App\Core;

final class Codes
{
    // 0/O/1/I/L kimi qarışıq simvollar çıxarılıb (paylaşım linkində asan oxunması üçün)
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function publicListingCode(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }
        return $code;
    }
}
