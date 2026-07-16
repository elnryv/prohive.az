<?php
declare(strict_types=1);

/**
 * Abunə/ödəniş (7.6). Tam Payriff axını Faza 4-ün işidir (Q7) — bu fazada
 * yalnız status/məbləğ görünən kart və "tezliklə" rejimli düymə göstərilir.
 */
final class Billing
{
    public function index(): void
    {
        Auth::requireLogin();
        $owner = Auth::user();

        View::render('owner/billing', [
            'title' => Lang::t('owner.billing_title') . ' — ' . Lang::t('app.name'),
            'owner' => $owner,
            'isVisible' => OwnerRepository::isVisible($owner),
            'price' => OwnerRepository::effectivePrice($owner),
            'paymentsEnabled' => PAYRIFF_SECRET_KEY !== '',
            'netice' => is_string($_GET['netice'] ?? null) ? $_GET['netice'] : null,
        ], 'layout');
    }

    /** "Ödə" düyməsi: Payriff sifarişi yaradır və ödəniş səhifəsinə yönləndirir (7.6, Q10). */
    public function pay(): void
    {
        Auth::requireLogin();
        $owner = Auth::user();

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header('Location: /sahib/abune');
            exit;
        }
        if ($owner['billing_status'] === 'free') {
            header('Location: /sahib/abune');
            exit;
        }
        if (PAYRIFF_SECRET_KEY === '') {
            header('Location: /sahib/abune?netice=tezlikle');
            exit;
        }

        $amount = OwnerRepository::effectivePrice($owner);
        $paymentId = PaymentRepository::create((int) $owner['id'], $amount, 1);

        try {
            $order = (new PayriffProvider())->createOrder(
                $amount,
                'Birlikdə Getdik abunə - ' . $owner['full_name'],
                (string) $paymentId
            );
            PaymentRepository::attachOrder($paymentId, $order['orderId'], $order['paymentUrl']);
            header('Location: ' . $order['paymentUrl']);
            exit;
        } catch (RuntimeException) {
            header('Location: /sahib/abune?netice=xeta');
            exit;
        }
    }
}
