<?php
declare(strict_types=1);

/**
 * Ev sorğuları: bütün SQL PDO prepared statements ilə (12.1 — sərbəst SQL qadağan).
 * Görünürlük qaydası 4.3-dəki reyestrə əsaslanır.
 */
final class HouseRepository
{
    private const VISIBILITY_SQL = "
        h.status = 'approved'
        AND (
            (o.billing_status = 'trial' AND o.trial_until >= CURDATE())
            OR (o.billing_status = 'paid' AND o.paid_until >= CURDATE())
            OR (o.billing_status = 'free')
        )
    ";

    /**
     * @param array{
     *   region_id?:int|null, checkin?:string|null, checkout?:string|null, guests?:int|null,
     *   price_min?:float|null, price_max?:float|null, amenity_ids?:int[], sort?:string
     * } $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public static function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildWhere($filters);
        $order = self::buildOrder($filters['sort'] ?? 'default');

        $countSql = "SELECT COUNT(*) AS c FROM houses h
                      JOIN owners o ON o.id = h.owner_id
                      JOIN regions r ON r.id = h.region_id
                      WHERE {$where}";
        $total = (int) (DB::one($countSql, $params)['c'] ?? 0);

        $maybeFullExpr = self::maybeFullExpr($filters, $params);

        $sql = "SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       (SELECT hp.filename FROM house_photos hp
                         WHERE hp.house_id = h.id AND hp.is_approved = 1
                         ORDER BY hp.is_cover DESC, hp.sort_order ASC LIMIT 1) AS cover_photo,
                       ({$maybeFullExpr}) AS maybe_full
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE {$where}
                ORDER BY maybe_full ASC, {$order}
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private static function buildWhere(array $filters): array
    {
        $conditions = [self::VISIBILITY_SQL];
        $params = [];

        if (!empty($filters['region_id'])) {
            $conditions[] = 'h.region_id = :region_id';
            $params[':region_id'] = (int) $filters['region_id'];
        }
        if (!empty($filters['price_min'])) {
            $conditions[] = 'h.price_night >= :price_min';
            $params[':price_min'] = (float) $filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $conditions[] = 'h.price_night <= :price_max';
            $params[':price_max'] = (float) $filters['price_max'];
        }
        if (!empty($filters['guests'])) {
            $conditions[] = 'h.capacity >= :guests';
            $params[':guests'] = (int) $filters['guests'];
        }
        $amenityIds = array_values(array_unique(array_filter(
            $filters['amenity_ids'] ?? [],
            static fn ($id) => (int) $id > 0
        )));
        if ($amenityIds !== []) {
            $placeholders = [];
            foreach ($amenityIds as $i => $id) {
                $ph = ":amenity{$i}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $id;
            }
            $count = count($amenityIds);
            $conditions[] = 'h.id IN (SELECT ha.house_id FROM house_amenities ha
                                       WHERE ha.amenity_id IN (' . implode(',', $placeholders) . ")
                                       GROUP BY ha.house_id
                                       HAVING COUNT(DISTINCT ha.amenity_id) = {$count})";
        }

        return [implode(' AND ', $conditions), $params];
    }

    /** @param array<string,mixed> $params (checkin/checkout param names appended here) */
    private static function maybeFullExpr(array $filters, array &$params): string
    {
        $checkin = $filters['checkin'] ?? null;
        $checkout = $filters['checkout'] ?? null;
        if (!$checkin || !$checkout) {
            return '0';
        }
        try {
            $lastNight = (new DateTimeImmutable($checkout))->modify('-1 day')->format('Y-m-d');
        } catch (Exception) {
            return '0';
        }
        $params[':mf_checkin'] = $checkin;
        $params[':mf_checkout'] = $lastNight;

        return 'SELECT EXISTS(SELECT 1 FROM house_calendar hc
                    WHERE hc.house_id = h.id AND hc.busy_date BETWEEN :mf_checkin AND :mf_checkout)';
    }

    private static function buildOrder(string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'h.price_night ASC',
            'price_desc' => 'h.price_night DESC',
            default => '(h.views_total - DATEDIFF(CURDATE(), DATE(h.created_at)) * 2) DESC, h.created_at DESC',
        };
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        $sql = "SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       o.full_name AS owner_name, o.created_at AS owner_created_at
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE h.slug = :slug AND " . self::VISIBILITY_SQL . '
                LIMIT 1';

        return DB::one($sql, [':slug' => $slug]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function photos(int $houseId): array
    {
        return DB::all(
            'SELECT * FROM house_photos WHERE house_id = :id AND is_approved = 1
             ORDER BY is_cover DESC, sort_order ASC, id ASC',
            [':id' => $houseId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function amenities(int $houseId): array
    {
        return DB::all(
            'SELECT a.* FROM amenities a
             JOIN house_amenities ha ON ha.amenity_id = a.id
             WHERE ha.house_id = :id AND a.is_active = 1
             ORDER BY a.sort_order ASC',
            [':id' => $houseId]
        );
    }

    /** @return string[] YYYY-MM-DD busy günlər */
    public static function busyDates(int $houseId, string $fromDate, string $toDate): array
    {
        $rows = DB::all(
            'SELECT busy_date FROM house_calendar
             WHERE house_id = :id AND busy_date BETWEEN :from AND :to',
            [':id' => $houseId, ':from' => $fromDate, ':to' => $toDate]
        );
        return array_map(static fn ($r) => $r['busy_date'], $rows);
    }

    /** @return array<int, array<string, mixed>> */
    public static function popular(int $limit = 6): array
    {
        $sql = 'SELECT h.*, r.slug AS region_slug, r.name_az AS region_name_az,
                       r.name_ru AS region_name_ru, r.name_en AS region_name_en,
                       (SELECT hp.filename FROM house_photos hp
                         WHERE hp.house_id = h.id AND hp.is_approved = 1
                         ORDER BY hp.is_cover DESC, hp.sort_order ASC LIMIT 1) AS cover_photo
                FROM houses h
                JOIN owners o ON o.id = h.owner_id
                JOIN regions r ON r.id = h.region_id
                WHERE ' . self::VISIBILITY_SQL . '
                ORDER BY h.views_total DESC, h.created_at DESC
                LIMIT :limit';

        $stmt = DB::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function incrementView(int $houseId): void
    {
        DB::query('UPDATE houses SET views_total = views_total + 1 WHERE id = :id', [':id' => $houseId]);
        self::bumpDailyStat($houseId, 'views');
        self::emitStatEvent($houseId, 'view');
    }

    public static function incrementWaClick(int $houseId): void
    {
        DB::query('UPDATE houses SET wa_clicks_total = wa_clicks_total + 1 WHERE id = :id', [':id' => $houseId]);
        self::bumpDailyStat($houseId, 'wa_clicks');
        self::emitStatEvent($houseId, 'wa_click');
    }

    /** Owner panelinin canlı sayğacı üçün (11.4) */
    private static function emitStatEvent(int $houseId, string $eventType): void
    {
        $house = DB::one('SELECT owner_id, title FROM houses WHERE id = :id', [':id' => $houseId]);
        if ($house === null) {
            return;
        }
        Sse::emit('owner_' . $house['owner_id'], $eventType, ['house_id' => $houseId, 'title' => $house['title']]);
    }

    private static function bumpDailyStat(int $houseId, string $column): void
    {
        $column = $column === 'wa_clicks' ? 'wa_clicks' : 'views';
        DB::query(
            "INSERT INTO stats_daily (house_id, stat_date, {$column})
             VALUES (:id, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE {$column} = {$column} + 1",
            [':id' => $houseId]
        );
    }

    // ===================== EV SAHİBİ TƏRƏFİ (bölmə 7) =====================

    /** Sahibin bütün evləri (status/görünürlükdən asılı olmayaraq) */
    public static function forOwner(int $ownerId): array
    {
        $sql = 'SELECT h.*, r.name_az AS region_name_az,
                       (SELECT COUNT(*) FROM house_photos hp WHERE hp.house_id = h.id) AS photo_count,
                       (SELECT hp.filename FROM house_photos hp
                         WHERE hp.house_id = h.id ORDER BY hp.is_cover DESC, hp.sort_order ASC LIMIT 1) AS cover_photo
                FROM houses h
                JOIN regions r ON r.id = h.region_id
                WHERE h.owner_id = :owner_id
                ORDER BY h.created_at DESC';
        return DB::all($sql, [':owner_id' => $ownerId]);
    }

    /** Sahiblik yoxlaması ilə tək ev (redaktə/foto/təqvim əməliyyatları üçün) */
    public static function findOwnedById(int $houseId, int $ownerId): ?array
    {
        return DB::one(
            'SELECT h.*, r.slug AS region_slug FROM houses h
             JOIN regions r ON r.id = h.region_id
             WHERE h.id = :id AND h.owner_id = :owner_id',
            [':id' => $houseId, ':owner_id' => $ownerId]
        );
    }

    /** @param array{title:string,region_id:int,village:?string,description:string,rooms:int,capacity:int,whatsapp_phone:string} $data */
    public static function createDraft(int $ownerId, array $data): int
    {
        DB::query(
            'INSERT INTO houses (owner_id, region_id, slug, title, village, description,
                                  price_night, rooms, capacity, whatsapp_phone, status, created_at)
             VALUES (:owner_id, :region_id, :slug, :title, :village, :description,
                     0, :rooms, :capacity, :whatsapp_phone, "draft", NOW())',
            [
                ':owner_id' => $ownerId,
                ':region_id' => $data['region_id'],
                ':slug' => self::generateUniqueSlug($data['title'], $data['region_id']),
                ':title' => $data['title'],
                ':village' => $data['village'],
                ':description' => $data['description'],
                ':rooms' => $data['rooms'],
                ':capacity' => $data['capacity'],
                ':whatsapp_phone' => $data['whatsapp_phone'],
            ]
        );
        return (int) DB::lastInsertId();
    }

    /** Yalnız icazə verilən sütunlar yenilənir (whitelist) */
    public static function updateFields(int $houseId, array $fields): void
    {
        $allowed = [
            'title', 'title_ru', 'title_en', 'village', 'description', 'description_ru', 'description_en',
            'price_night', 'price_weekend', 'rooms', 'capacity', 'whatsapp_phone', 'map_lat', 'map_lng',
        ];
        $sets = [];
        $params = [':id' => $houseId];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $sets[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        if ($sets === []) {
            return;
        }
        $sql = 'UPDATE houses SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id';
        DB::query($sql, $params);
    }

    public static function setAmenities(int $houseId, array $amenityIds): void
    {
        DB::query('DELETE FROM house_amenities WHERE house_id = :id', [':id' => $houseId]);
        foreach (array_unique(array_map('intval', $amenityIds)) as $amenityId) {
            if ($amenityId > 0) {
                DB::query(
                    'INSERT IGNORE INTO house_amenities (house_id, amenity_id) VALUES (:h, :a)',
                    [':h' => $houseId, ':a' => $amenityId]
                );
            }
        }
    }

    /** draft/rejected → pending. approved evlər buradan keçmir (redaktə qaydası, bölmə 4.5) */
    public static function submitForApproval(int $houseId): bool
    {
        $stmt = DB::query(
            "UPDATE houses SET status = 'pending', reject_reason = NULL, updated_at = NOW()
             WHERE id = :id AND status IN ('draft', 'rejected')",
            [':id' => $houseId]
        );
        return $stmt->rowCount() > 0;
    }

    // ===================== Admin təsdiq növbəsi (9.2) =====================

    /** @return array<int, array<string,mixed>> */
    public static function pendingHouses(): array
    {
        return DB::all(
            "SELECT h.*, o.full_name AS owner_name, o.phone AS owner_phone, r.name_az AS region_name_az
             FROM houses h
             JOIN owners o ON o.id = h.owner_id
             JOIN regions r ON r.id = h.region_id
             WHERE h.status = 'pending'
             ORDER BY h.created_at ASC"
        );
    }

    /** Status/görünürlükdən asılı olmadan (admin baxışı üçün) tam ev məlumatı */
    public static function findByIdAdmin(int $houseId): ?array
    {
        return DB::one(
            'SELECT h.*, o.full_name AS owner_name, o.phone AS owner_phone, r.name_az AS region_name_az
             FROM houses h
             JOIN owners o ON o.id = h.owner_id
             JOIN regions r ON r.id = h.region_id
             WHERE h.id = :id',
            [':id' => $houseId]
        );
    }

    public static function approve(int $houseId): void
    {
        DB::query(
            "UPDATE houses SET status = 'approved', reject_reason = NULL, updated_at = NOW() WHERE id = :id",
            [':id' => $houseId]
        );
        DB::query(
            'UPDATE house_photos SET is_approved = 1 WHERE house_id = :id',
            [':id' => $houseId]
        );
    }

    public static function reject(int $houseId, string $reason): void
    {
        DB::query(
            "UPDATE houses SET status = 'rejected', reject_reason = :reason, updated_at = NOW() WHERE id = :id",
            [':id' => $houseId, ':reason' => $reason]
        );
    }

    /** @return array<int, array<string,mixed>> approved ev üzərinə sonradan əlavə olunub hələ təsdiqlənməmiş fotolar */
    public static function pendingPhotos(): array
    {
        return DB::all(
            "SELECT hp.*, h.title AS house_title, h.slug AS house_slug
             FROM house_photos hp
             JOIN houses h ON h.id = hp.house_id
             WHERE hp.is_approved = 0
             ORDER BY hp.created_at ASC"
        );
    }

    public static function approvePhoto(int $photoId): void
    {
        DB::query('UPDATE house_photos SET is_approved = 1 WHERE id = :id', [':id' => $photoId]);
    }

    /** @return array<string,mixed>|null silinmədən əvvəl fayl yolu üçün sətir qaytarır */
    public static function findPhotoById(int $photoId): ?array
    {
        return DB::one('SELECT * FROM house_photos WHERE id = :id', [':id' => $photoId]);
    }

    public static function deletePhotoById(int $photoId): void
    {
        DB::query('DELETE FROM house_photos WHERE id = :id', [':id' => $photoId]);
    }

    /** Owner-in gördüyü bütün fotolar (təsdiqlənməmişlər daxil) */
    public static function allPhotos(int $houseId): array
    {
        return DB::all(
            'SELECT * FROM house_photos WHERE house_id = :id ORDER BY is_cover DESC, sort_order ASC, id ASC',
            [':id' => $houseId]
        );
    }

    public static function findPhotoOwned(int $photoId, int $houseId): ?array
    {
        return DB::one(
            'SELECT * FROM house_photos WHERE id = :pid AND house_id = :hid',
            [':pid' => $photoId, ':hid' => $houseId]
        );
    }

    public static function addPhoto(int $houseId, string $filename, bool $isVideo, bool $makeCover): int
    {
        $nextSort = (int) (DB::one(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM house_photos WHERE house_id = :id',
            [':id' => $houseId]
        )['n'] ?? 0);

        if ($makeCover) {
            DB::query('UPDATE house_photos SET is_cover = 0 WHERE house_id = :id', [':id' => $houseId]);
        }

        DB::query(
            'INSERT INTO house_photos (house_id, filename, is_cover, is_video, is_approved, sort_order, created_at)
             VALUES (:h, :f, :cover, :video, 0, :sort, NOW())',
            [
                ':h' => $houseId,
                ':f' => $filename,
                ':cover' => $makeCover ? 1 : 0,
                ':video' => $isVideo ? 1 : 0,
                ':sort' => $nextSort,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function deletePhoto(int $photoId, int $houseId): void
    {
        DB::query('DELETE FROM house_photos WHERE id = :id AND house_id = :hid', [':id' => $photoId, ':hid' => $houseId]);
    }

    public static function setCoverPhoto(int $photoId, int $houseId): void
    {
        DB::query('UPDATE house_photos SET is_cover = 0 WHERE house_id = :id', [':id' => $houseId]);
        DB::query('UPDATE house_photos SET is_cover = 1 WHERE id = :id AND house_id = :hid', [':id' => $photoId, ':hid' => $houseId]);
    }

    public static function toggleBusyDay(int $houseId, string $date): bool
    {
        $existing = DB::one(
            'SELECT 1 FROM house_calendar WHERE house_id = :id AND busy_date = :d',
            [':id' => $houseId, ':d' => $date]
        );
        if ($existing !== null) {
            DB::query('DELETE FROM house_calendar WHERE house_id = :id AND busy_date = :d', [':id' => $houseId, ':d' => $date]);
            return false;
        }
        DB::query('INSERT INTO house_calendar (house_id, busy_date) VALUES (:id, :d)', [':id' => $houseId, ':d' => $date]);
        return true;
    }

    public static function markRange(int $houseId, string $from, string $to, bool $busy): void
    {
        if ($busy) {
            $period = new DatePeriod(new DateTimeImmutable($from), new DateInterval('P1D'), (new DateTimeImmutable($to))->modify('+1 day'));
            foreach ($period as $day) {
                DB::query(
                    'INSERT IGNORE INTO house_calendar (house_id, busy_date) VALUES (:id, :d)',
                    [':id' => $houseId, ':d' => $day->format('Y-m-d')]
                );
            }
        } else {
            DB::query(
                'DELETE FROM house_calendar WHERE house_id = :id AND busy_date BETWEEN :from AND :to',
                [':id' => $houseId, ':from' => $from, ':to' => $to]
            );
        }
    }

    /** @return array<int, array{stat_date:string, views:int, wa_clicks:int}> son 30 gün, boş günlər 0 ilə doldurulub */
    public static function stats30d(int $houseId): array
    {
        $rows = DB::all(
            'SELECT stat_date, views, wa_clicks FROM stats_daily
             WHERE house_id = :id AND stat_date >= CURDATE() - INTERVAL 29 DAY
             ORDER BY stat_date ASC',
            [':id' => $houseId]
        );
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['stat_date']] = ['views' => (int) $r['views'], 'wa_clicks' => (int) $r['wa_clicks']];
        }
        $out = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $out[] = [
                'stat_date' => $date,
                'views' => $byDate[$date]['views'] ?? 0,
                'wa_clicks' => $byDate[$date]['wa_clicks'] ?? 0,
            ];
        }
        return $out;
    }

    private static function generateUniqueSlug(string $title, int $regionId): string
    {
        $base = self::slugify($title);
        $region = DB::one('SELECT slug FROM regions WHERE id = :id', [':id' => $regionId]);
        $base .= '-' . ($region['slug'] ?? 'ev');

        $slug = $base;
        $i = 1;
        while (DB::one('SELECT id FROM houses WHERE slug = :s', [':s' => $slug]) !== null) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    private static function slugify(string $text): string
    {
        $map = [
            'ə' => 'e', 'Ə' => 'e', 'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o',
            'ü' => 'u', 'Ü' => 'u', 'ş' => 's', 'Ş' => 's', 'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'ev';
    }
}
