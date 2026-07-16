<?php
declare(strict_types=1);

final class Region
{
    public function show(array $params): void
    {
        $region = RegionRepository::findBySlug($params['slug'] ?? '');
        if ($region === null) {
            $this->notFound();
            return;
        }

        $filters = ListingFilters::parse($_GET);
        $filters['region_id'] = (int) $region['id'];

        $result = HouseRepository::search($filters, $filters['limit'], $filters['offset']);
        $amenities = AmenityRepository::listActive();

        $data = [
            'title' => $region['name_az'] . ' — ' . Lang::t('app.name'),
            'description' => $region['tagline_az'] ?? Lang::t('app.tagline'),
            'region' => $region,
            'houses' => $result['items'],
            'total' => $result['total'],
            'filters' => $filters,
            'amenities' => $amenities,
            'hasMore' => ($filters['offset'] + count($result['items'])) < $result['total'],
            'baseUrl' => '/bolge/' . $region['slug'],
        ];

        if (ListingFilters::isAjax()) {
            View::render('site/partials/house_cards', $data, null);
            return;
        }

        View::render('site/region', $data);
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('site/404');
    }
}
