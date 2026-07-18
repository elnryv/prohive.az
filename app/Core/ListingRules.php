<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Elanlarla bağlı sadə hesablama qaydaları (bölmə 6.2, 6.5, 6.7) — controller-lər
 * və cron skriptləri arasında təkrar istifadə olunur.
 */
final class ListingRules
{
    /**
     * partials/listing_card.php-nin gözlədiyi sütun adları (cat_az/from_az/to_az və s.)
     * ilə uyğun standart JOIN+SELECT fraqmenti — bütün elan sorğularında təkrar istifadə olunur.
     */
    public const SELECT_SQL = "l.*,
        c.icon, c.slug as category_slug,
        c.name_az as cat_az, c.name_ru as cat_ru, c.name_en as cat_en,
        fl.name_az as from_az, fl.name_ru as from_ru, fl.name_en as from_en, fl.is_baku as from_is_baku,
        tl.name_az as to_az, tl.name_ru as to_ru, tl.name_en as to_en, tl.is_baku as to_is_baku";

    public const FROM_SQL = 'FROM listings l
        JOIN categories c ON c.id = l.category_id
        JOIN locations fl ON fl.id = l.from_location_id
        JOIN locations tl ON tl.id = l.to_location_id';

    public static function scopeFor(bool $fromIsBaku, bool $toIsBaku): string
    {
        return ($fromIsBaku && $toIsBaku) ? 'baku' : 'intercity';
    }

    public static function initialExpiresAt(?string $moveDate): string
    {
        if ($moveDate !== null && $moveDate !== '') {
            return date('Y-m-d 23:59:59', strtotime($moveDate . ' +1 day'));
        }
        $hours = (int) Settings::get('listing_auto_close_hours', '72');
        return date('Y-m-d H:i:s', time() + $hours * 3600);
    }

    /** Ləğv/yenidən açılmada (Q-Y5): +72 saat, sabit. */
    public static function reopenExpiresAt(): string
    {
        return date('Y-m-d H:i:s', time() + 72 * 3600);
    }

    /** Tamamlanma müddəti (bölmə 6.7): move_date+1 gün, ya da accepted_at+72 saat. */
    public static function completionDueAt(?string $moveDate, string $acceptedAt): string
    {
        if ($moveDate !== null && $moveDate !== '') {
            return date('Y-m-d 23:59:59', strtotime($moveDate . ' +1 day'));
        }
        return date('Y-m-d H:i:s', strtotime($acceptedAt) + 72 * 3600);
    }

    /**
     * Sürücü lentinə canlı (SSE) düşəcək kartın hazır HTML-i — sorğu + render PUBLISH
     * ANINDA, bir yerdə edilir ki, client tərəfi ayrıca fetch()-ə ehtiyac duymasın.
     * Əvvəlki dizaynda bu əlavə round-trip (`/surucu/lent/kart/{id}`) özü müstəqil bir
     * uğursuzluq nöqtəsi idi (FAZA 20) — SSE hadisəsi çatsa belə, sonrakı fetch
     * uğursuz/gec olarsa kart heç görünmürdü.
     *
     * @return array{html:string, scope:string}|null
     */
    public static function renderFeedCard(int $listingId): ?array
    {
        $sql = 'SELECT ' . self::SELECT_SQL . ' ' . self::FROM_SQL . "
             WHERE l.id = ? AND l.status = 'active' LIMIT 1";
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch();
        if ($listing === false) {
            return null;
        }
        return [
            'html' => View::capture('partials/listing_card', ['listing' => $listing, 'href' => '/surucu/elan/' . $listingId]),
            'scope' => $listing['scope'],
        ];
    }
}
