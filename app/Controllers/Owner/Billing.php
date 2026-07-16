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
        ], 'layout');
    }
}
