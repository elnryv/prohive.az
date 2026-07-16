<?php
declare(strict_types=1);

interface PaymentGateway
{
    /** @return array{orderId:string, paymentUrl:string} */
    public function createOrder(float $amount, string $description, string $externalRef): array;

    /** @return string 'APPROVED'|'DECLINED'|'CANCELED'|'PENDING'|'UNKNOWN' */
    public function getOrderStatus(string $orderId): string;
}
