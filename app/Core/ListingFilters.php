<?php
declare(strict_types=1);

/**
 * Bölgə/axtarış səhifələri üçün ortaq filtr sanitizasiyası.
 * Bütün dəyərlər tipə görə cast olunur — SQL-ə birbaşa girmir (DB.php bound params
 * ilə istifadə olunur), amma buradakı validasiya də əlavə müdafiə qatıdır.
 */
final class ListingFilters
{
    private const ALLOWED_SORT = ['default', 'price_asc', 'price_desc'];
    private const PER_PAGE = 12;

    /** @param array<string, mixed> $get */
    public static function parse(array $get): array
    {
        $checkin = self::parseDate($get['checkin'] ?? null);
        $checkout = self::parseDate($get['checkout'] ?? null);
        if ($checkin !== null && $checkout !== null && $checkout <= $checkin) {
            $checkout = null;
            $checkin = null;
        }

        $amenityIds = [];
        if (isset($get['amenities']) && is_array($get['amenities'])) {
            foreach ($get['amenities'] as $a) {
                if (is_numeric($a) && (int) $a > 0) {
                    $amenityIds[] = (int) $a;
                }
            }
        }

        $sort = is_string($get['sort'] ?? null) ? $get['sort'] : 'default';
        if (!in_array($sort, self::ALLOWED_SORT, true)) {
            $sort = 'default';
        }

        $page = isset($get['page']) && is_numeric($get['page']) ? max(1, (int) $get['page']) : 1;

        return [
            'price_min' => self::parseFloat($get['price_min'] ?? null),
            'price_max' => self::parseFloat($get['price_max'] ?? null),
            'guests' => self::parseInt($get['guests'] ?? null),
            'checkin' => $checkin,
            'checkout' => $checkout,
            'amenity_ids' => $amenityIds,
            'sort' => $sort,
            'page' => $page,
            'limit' => self::PER_PAGE,
            'offset' => (self::PER_PAGE) * ($page - 1),
        ];
    }

    private static function parseDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($d === false || $d->format('Y-m-d') !== $value) {
            return null;
        }
        return $value;
    }

    private static function parseFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $f = (float) $value;
        return $f > 0 ? $f : null;
    }

    private static function parseInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $i = (int) $value;
        return $i > 0 ? $i : null;
    }

    public static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
