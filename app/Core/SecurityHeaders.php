<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bütün cavablara tətbiq olunan təhlükəsizlik başlıqları — bax CLAUDE.md
 * bölmə 6 ("CSP header"). CSP-də 'unsafe-inline' script/style üçün
 * saxlanılıb, çünki bütün Views fayllarında inline <script> və style="..."
 * geniş istifadə olunur (nonce-əsaslı refaktorinq gələcək iş kimi qalır) —
 * amma object-src/frame-ancestors/base-uri/form-action sərt şəkildə
 * məhdudlaşdırılıb, bu da əsas hücum səthini (kənar domenə skript/frame
 * yükləmə, clickjacking, form-hijacking) bağlayır.
 */
final class SecurityHeaders
{
    public static function apply(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        if (Env::getBool('SESSION_SECURE', true)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; "
            . "font-src 'self'; "
            . "connect-src 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'none'"
        );

        header_remove('X-Powered-By');
    }
}
