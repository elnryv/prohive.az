<?php

declare(strict_types=1);

namespace App\Providers;

/**
 * Ödəniş başlatma nəticəsi — kuryerin yönləndiriləcəyi provayder səhifəsi.
 */
final class PaymentSession
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $redirectUrl,
    ) {
    }
}
