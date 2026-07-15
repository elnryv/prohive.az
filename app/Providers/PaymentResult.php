<?php

declare(strict_types=1);

namespace App\Providers;

/**
 * Webhook doğrulama nəticəsi. `gecerli=false` olduqda `ugurlu` mənasızdır —
 * imza doğrulanmayan sorğu heç vaxt ödəniş nəticəsi kimi emal edilməməlidir
 * (bax CLAUDE.md bölmə PAY-9.1.1).
 */
final class PaymentResult
{
    public function __construct(
        public readonly bool $gecerli,
        public readonly bool $ugurlu,
        public readonly string $orderId,
        public readonly array $ham,
    ) {
    }
}
