<?php
declare(strict_types=1);

final class Dashboard
{
    public function index(): void
    {
        Auth::requireLogin();
        $owner = Auth::user();

        $houses = HouseRepository::forOwner((int) $owner['id']);
        $stats = [];
        foreach ($houses as $h) {
            $stats[(int) $h['id']] = HouseRepository::stats30d((int) $h['id']);
        }

        View::render('owner/dashboard', [
            'title' => Lang::t('owner.dashboard_title') . ' — ' . Lang::t('app.name'),
            'owner' => $owner,
            'houses' => $houses,
            'stats' => $stats,
            'isVisible' => OwnerRepository::isVisible($owner),
            'price' => OwnerRepository::effectivePrice($owner),
        ], 'layout');
    }
}
