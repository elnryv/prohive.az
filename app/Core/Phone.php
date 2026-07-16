<?php
declare(strict_types=1);

final class Phone
{
    public static function digitsOnly(string $input): string
    {
        return preg_replace('/\D+/', '', $input) ?? '';
    }

    /**
     * Tam normalizasiya (5.1): owners.phone / houses.whatsapp_phone üçün.
     * Nəticə həmişə 994XXXXXXXXX formatında 12 rəqəmdir.
     *
     * @throws InvalidArgumentException nəticə 12 rəqəm olmadıqda
     */
    public static function normalize(string $input): string
    {
        $digits = self::digitsOnly($input);

        if (str_starts_with($digits, '0')) {
            $digits = '994' . substr($digits, 1);
        } elseif (str_starts_with($digits, '994')) {
            // olduğu kimi
        } elseif (strlen($digits) === 9) {
            $digits = '994' . $digits;
        }

        if (strlen($digits) !== 12) {
            throw new InvalidArgumentException('Telefon nömrəsi düzgün formatda deyil');
        }

        return $digits;
    }

    public static function isValid(string $input): bool
    {
        try {
            self::normalize($input);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /** Admin axtarışı üçün: WhatsApp linkini yaradar (994... formatında olmalıdır) */
    public static function toWaLink(string $normalizedPhone, string $message = ''): string
    {
        $url = 'https://wa.me/' . $normalizedPhone;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }
}
