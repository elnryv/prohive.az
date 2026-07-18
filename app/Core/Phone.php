<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Azərbaycan mobil nömrə normalizasiyası → 994XXXXXXXXX (12 rəqəm, boşluqsuz).
 * Qəbul edilən formatlar: 0501234567, 501234567, +994501234567, 994501234567,
 * boşluq/tire/mötərizə ilə yazılmış istənilən variasiya.
 */
final class Phone
{
    private const VALID_PREFIXES = ['10', '50', '51', '55', '60', '70', '77', '99'];

    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // 00994XXXXXXXXX -> 994XXXXXXXXX
        if (str_starts_with($digits, '00994')) {
            $digits = substr($digits, 2);
        }

        // 994XXXXXXXXX (12 rəqəm) artıq tam formatdadır
        if (str_starts_with($digits, '994') && strlen($digits) === 12) {
            $national = substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            // 0XXXXXXXXX -> XXXXXXXXX
            $national = substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            // XXXXXXXXX (kod artıq yoxdur)
            $national = $digits;
        } else {
            return null;
        }

        $prefix = substr($national, 0, 2);
        if (!in_array($prefix, self::VALID_PREFIXES, true)) {
            return null;
        }

        return '994' . $national;
    }

    public static function isValid(string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** Göstərim üçün: 994501234567 -> +994 50 123 45 67 */
    public static function display(string $normalized): string
    {
        if (!preg_match('/^994(\d{2})(\d{3})(\d{2})(\d{2})$/', $normalized, $m)) {
            return $normalized;
        }
        return sprintf('+994 %s %s %s %s', $m[1], $m[2], $m[3], $m[4]);
    }

    /** WhatsApp linki üçün beynəlxalq format (əlavə işarə yoxdur). */
    public static function whatsappNumber(string $normalized): string
    {
        return $normalized;
    }

    /**
     * Saxlanılan 994XXXXXXXXX formatını qeydiyyat/profil formasındakı prefiks-seçim
     * inputuna uyğun {prefix: "0XX", number: "XXXXXXX"} cütünə bölür (FAZA 16).
     *
     * @return array{prefix:string, number:string}
     */
    public static function splitForInput(string $normalized): array
    {
        if (!preg_match('/^994(\d{2})(\d{7})$/', $normalized, $m)) {
            return ['prefix' => '', 'number' => ''];
        }
        return ['prefix' => '0' . $m[1], 'number' => $m[2]];
    }
}
