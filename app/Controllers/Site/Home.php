<?php
declare(strict_types=1);

final class Home
{
    public function index(): void
    {
        $regions = RegionRepository::listActive();
        $popular = HouseRepository::popular(6);

        $chipAmenities = [
            'home.chip_mountain' => 'Dağ mənzərəsi',
            'home.chip_pool' => 'Hovuz',
            'home.chip_family' => 'Uşaq üçün uyğun',
            'home.chip_sea' => 'Dəniz mənzərəsi',
            'home.chip_bbq' => 'Mangal yeri',
        ];
        $chips = [];
        foreach ($chipAmenities as $labelKey => $nameAz) {
            $id = AmenityRepository::findIdByNameAz($nameAz);
            if ($id !== null) {
                $chips[] = ['label' => Lang::t($labelKey), 'amenity_id' => $id];
            }
        }

        View::render('site/home', [
            'title' => Lang::t('home.hero_title') . ' — ' . Lang::t('app.name'),
            'description' => Lang::t('home.hero_subtitle'),
            'regions' => $regions,
            'popular' => $popular,
            'chips' => $chips,
        ]);
    }
}
