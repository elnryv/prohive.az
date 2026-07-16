<?php
declare(strict_types=1);

final class Search
{
    public function index(): void
    {
        $filters = ListingFilters::parse($_GET);

        $regionSlug = is_string($_GET['region'] ?? null) ? $_GET['region'] : '';
        $region = null;
        if ($regionSlug !== '' && $regionSlug !== 'hamisi') {
            $region = RegionRepository::findBySlug($regionSlug);
            if ($region !== null) {
                $filters['region_id'] = (int) $region['id'];
            }
        }

        $result = HouseRepository::search($filters, $filters['limit'], $filters['offset']);
        $regions = RegionRepository::listActive();
        $amenities = AmenityRepository::listActive();

        $data = [
            'title' => Lang::t('search.title') . ' — ' . Lang::t('app.name'),
            'description' => Lang::t('app.tagline'),
            'houses' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'regions' => $regions,
            'amenities' => $amenities,
            'selectedRegion' => $region,
            'hasMore' => ($filters['offset'] + count($result['items'])) < $result['total'],
            'baseUrl' => '/axtar',
        ];

        if (ListingFilters::isAjax()) {
            View::render('site/partials/house_cards', $data, null);
            return;
        }

        View::render('site/search', $data);
    }
}
