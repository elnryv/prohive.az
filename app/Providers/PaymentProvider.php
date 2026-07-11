<?php

declare(strict_types=1);

namespace App\Providers;

/**
 * Provayder-neytral ödəniş interfeysi — bax CLAUDE.md bölmə 9.1.
 * Recurring/avtomatik kart çəkmə metodu QƏSDƏN yoxdur (yarım-avtomatik model,
 * PAY-9.1 qeydinə uyğun).
 */
interface PaymentProvider
{
    public function baslat(int $kuryeId, float $mebleg, string $orderId): PaymentSession;

    public function callbackDogrula(array $data): PaymentResult;
}
