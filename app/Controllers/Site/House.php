<?php
declare(strict_types=1);

final class House
{
    public function show(array $params): void
    {
        $house = HouseRepository::findBySlug($params['slug'] ?? '');
        if ($house === null) {
            http_response_code(404);
            View::render('site/404', ['title' => Lang::t('house.not_found')]);
            return;
        }

        $this->countView((int) $house['id']);

        $photos = HouseRepository::photos((int) $house['id']);
        $amenities = HouseRepository::amenities((int) $house['id']);

        $calFrom = new DateTimeImmutable('first day of this month');
        $calTo = new DateTimeImmutable('last day of next month');
        $busy = array_flip(HouseRepository::busyDates(
            (int) $house['id'],
            $calFrom->format('Y-m-d'),
            $calTo->format('Y-m-d')
        ));

        $lang = Lang::current();
        $localizedTitle = match ($lang) {
            'ru' => $house['title_ru'] ?: $house['title'],
            'en' => $house['title_en'] ?: $house['title'],
            default => $house['title'],
        };
        $localizedDescription = match ($lang) {
            'ru' => $house['description_ru'] ?: $house['description'],
            'en' => $house['description_en'] ?: $house['description'],
            default => $house['description'],
        };
        $descriptionIsFallback = $lang !== 'az' && match ($lang) {
            'ru' => empty($house['description_ru']),
            'en' => empty($house['description_en']),
            default => false,
        };

        $regionName = match ($lang) {
            'ru' => $house['region_name_ru'],
            'en' => $house['region_name_en'],
            default => $house['region_name_az'],
        };

        View::render('site/house', [
            'title' => $localizedTitle . ' — ' . $regionName . ' | ' . Lang::t('app.name'),
            'description' => mb_substr(strip_tags((string) $localizedDescription), 0, 160),
            'house' => $house,
            'localizedTitle' => $localizedTitle,
            'localizedDescription' => $localizedDescription,
            'descriptionIsFallback' => $descriptionIsFallback,
            'regionName' => $regionName,
            'photos' => $photos,
            'amenities' => $amenities,
            'calFrom' => $calFrom,
            'calTo' => $calTo,
            'busy' => $busy,
        ]);
    }

    private function countView(int $houseId): void
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (Dedupe::looksLikeBot($ua)) {
            return;
        }
        $ip = RateLimit::clientIp();
        if (Dedupe::shouldCount("view:{$houseId}:{$ip}", 1800)) {
            HouseRepository::incrementView($houseId);
        }
    }
}
