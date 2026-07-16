<?php
declare(strict_types=1);

/**
 * POST /odenis/callback (bölmə 8.4) — TƏHLÜKƏSİZLİK QAYDASI:
 * Callback gövdəsinə güvənilmir. Status HƏMİŞƏ Payriff-in özündən
 * (getOrderStatus) yenidən sorğulanır. Hər halda HTTP 200 qaytarılır
 * (Payriff təkrar cəhdlərini dayandırmaq üçün).
 */
final class PaymentCallback
{
    private ?PaymentGateway $gateway;

    public function __construct(?PaymentGateway $gateway = null)
    {
        $this->gateway = $gateway;
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->process(file_get_contents('php://input') ?: '');
    }

    /** HTTP girişindən ayrı — test üçün gövdəni birbaşa qəbul edir */
    public function process(string $raw): void
    {
        $body = json_decode($raw, true);
        $orderId = is_array($body) ? (string) ($body['orderId'] ?? '') : '';

        $payment = $orderId !== '' ? PaymentRepository::findByOrderId($orderId) : null;
        if ($payment === null) {
            $this->ok();
            return;
        }

        try {
            $gateway = $this->gateway ?? new PayriffProvider();
            $status = $gateway->getOrderStatus($orderId);
        } catch (RuntimeException) {
            // Payriff-ə çata bilmədik — recheck cron (8.5) sonra yenidən yoxlayacaq.
            $this->ok();
            return;
        }

        if ($status === 'APPROVED') {
            PaymentRepository::applyApproval((int) $payment['id']);
        } elseif (in_array($status, ['DECLINED', 'CANCELED'], true)) {
            PaymentRepository::markStatus((int) $payment['id'], strtolower($status));
        }

        $this->ok();
    }

    private function ok(): void
    {
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }
}
