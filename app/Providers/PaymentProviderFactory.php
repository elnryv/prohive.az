<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Env;

final class PaymentProviderFactory
{
    public static function current(): PaymentProvider
    {
        $ad = Env::get('PAYMENT_PROVIDER', 'birbank');

        return match ($ad) {
            'birbank' => new BirbankProvider(),
            'payriff' => new PayriffProvider(),
            default => throw new \RuntimeException("Naməlum ödəniş provayderi: {$ad}"),
        };
    }
}
