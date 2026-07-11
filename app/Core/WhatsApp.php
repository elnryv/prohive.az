<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Click-to-Chat link generasiyası — bax CLAUDE.md bölmə 6.4. Platforma söhbətə
 * qarışmır/saxlamır, yalnız ön-doldurulmuş wa.me linkini açır.
 */
final class WhatsApp
{
    public static function link(string $whatsappNumber, string $text): string
    {
        return 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($text);
    }
}
