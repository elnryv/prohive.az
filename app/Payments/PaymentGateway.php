<?php

declare(strict_types=1);

namespace App\Payments;

interface PaymentGateway
{
    /**
     * @return array{ok:bool, order_id?:string, payment_url?:string, error?:string}
     */
    public function createOrder(float $amount, string $currency, string $description, string $externalTrackId): array;

    /**
     * @return array{ok:bool, status:string, raw?:array}
     */
    public function getOrderStatus(string $orderId): array;
}
