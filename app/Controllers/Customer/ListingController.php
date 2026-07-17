<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Codes;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\ListingRules;
use App\Core\OgImage;
use App\Core\Sse;
use App\Core\Upload;
use App\Core\View;
use App\Core\WebPush;

final class ListingController
{
    private const MAX_ACTIVE_PER_DAY = 5;
    private const MAX_PHOTOS = 6;

    public function createForm(): void
    {
        Auth::requireRole('customer', '/giris');
        $this->renderForm([], []);
    }

    private function renderForm(array $errors, array $old): void
    {
        $categories = DB::conn()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
        $locations = DB::conn()->query('SELECT * FROM locations WHERE is_active = 1 ORDER BY is_baku DESC, sort_order')->fetchAll();

        View::render('customer/listing_form', [
            'pageTitle' => t('nav.new_listing'),
            'categories' => $categories,
            'locations' => $locations,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('customer', '/giris');

        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $customerId = Auth::id();
        $errors = [];

        $countStmt = DB::conn()->prepare(
            "SELECT COUNT(*) c FROM listings WHERE customer_id = ? AND status = 'active' AND created_at >= CURDATE()"
        );
        $countStmt->execute([$customerId]);
        if ((int) $countStmt->fetch()['c'] >= self::MAX_ACTIVE_PER_DAY) {
            $errors['limit'] = 'listing.daily_limit_reached';
            $this->renderForm($errors, $_POST);
            return;
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $fromLocationId = (int) ($_POST['from_location_id'] ?? 0);
        $toLocationId = (int) ($_POST['to_location_id'] ?? 0);
        $fromDetail = trim((string) ($_POST['from_detail'] ?? '')) ?: null;
        $toDetail = trim((string) ($_POST['to_detail'] ?? '')) ?: null;
        $dateMode = (string) ($_POST['date_mode'] ?? 'agreement');
        $moveDate = $dateMode === 'exact' ? (string) ($_POST['move_date'] ?? '') : null;
        $isUrgent = !empty($_POST['is_urgent']) ? 1 : 0;
        $description = trim((string) ($_POST['description'] ?? ''));

        $category = $this->fetchOne('categories', $categoryId);
        if ($category === null) {
            $errors['category_id'] = 'listing.category_required';
        }

        $fromLocation = $this->fetchOne('locations', $fromLocationId);
        if ($fromLocation === null) {
            $errors['from_location_id'] = 'listing.location_required';
        }
        $toLocation = $this->fetchOne('locations', $toLocationId);
        if ($toLocation === null) {
            $errors['to_location_id'] = 'listing.location_required';
        }

        if ($dateMode === 'exact') {
            $ts = strtotime($moveDate ?? '');
            if ($ts === false || $ts < strtotime('today')) {
                $errors['move_date'] = 'listing.date_invalid';
            }
        }

        if ($description === '') {
            $errors['description'] = 'listing.description_required';
        }

        $photoFiles = [];
        if (!empty($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $count = count($_FILES['photos']['name']);
            if ($count > self::MAX_PHOTOS) {
                $errors['photos'] = 'listing.too_many_photos';
            } else {
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $file = [
                        'name' => $_FILES['photos']['name'][$i],
                        'type' => $_FILES['photos']['type'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'error' => $_FILES['photos']['error'][$i],
                        'size' => $_FILES['photos']['size'][$i],
                    ];
                    $result = Upload::storeImage($file, (string) Config::get('upload.listing_photo_dir'), (int) Config::get('upload.max_photo_bytes'));
                    if ($result['ok']) {
                        $photoFiles[] = $result['filename'];
                    }
                }
            }
        }

        if ($errors !== []) {
            $this->renderForm($errors, $_POST);
            return;
        }

        $scope = ListingRules::scopeFor((bool) $fromLocation['is_baku'], (bool) $toLocation['is_baku']);
        $expiresAt = ListingRules::initialExpiresAt($moveDate);
        $publicCode = $this->generateUniqueCode();

        $stmt = DB::conn()->prepare(
            'INSERT INTO listings (public_code, customer_id, category_id, from_location_id, from_detail,
                to_location_id, to_detail, scope, move_date, is_urgent, description, status, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", ?)'
        );
        $stmt->execute([
            $publicCode, $customerId, $categoryId, $fromLocationId, $fromDetail,
            $toLocationId, $toDetail, $scope, $moveDate ?: null, $isUrgent, $description, $expiresAt,
        ]);
        $listingId = (int) DB::conn()->lastInsertId();

        foreach ($photoFiles as $idx => $filename) {
            $ph = DB::conn()->prepare('INSERT INTO listing_photos (listing_id, filename, sort_order) VALUES (?, ?, ?)');
            $ph->execute([$listingId, $filename, $idx]);
        }

        $ev = DB::conn()->prepare('INSERT INTO listing_events (listing_id, event, actor_user_id) VALUES (?, "created", ?)');
        $ev->execute([$listingId, $customerId]);

        OgImage::saveForListing(
            $publicCode,
            \App\Core\Lang::field($fromLocation, 'name'),
            \App\Core\Lang::field($toLocation, 'name'),
            $category['icon'],
            \App\Core\Lang::field($category, 'name')
        );

        Sse::publish('feed', 'listing_new', ['listing_id' => $listingId, 'scope' => $scope]);
        $this->notifyApprovedDrivers($listingId, $fromLocation, $toLocation);

        header('Location: /musteri/elan/' . $listingId . '?yaradildi=1');
    }

    public function show(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        $id = (int) $params['id'];

        $listing = $this->fetchListingForCustomer($id, (int) Auth::id());
        if ($listing === null) {
            http_response_code(404);
            View::render('site/404', []);
            return;
        }

        $photos = DB::conn()->prepare('SELECT * FROM listing_photos WHERE listing_id = ? ORDER BY sort_order');
        $photos->execute([$id]);

        // Q-Y3: u.phone yalnız BURADA seçilir çünki bu metod artıq yuxarıda
        // fetchListingForCustomer() ilə elanın MƏHZ bu müştəriyə aid olduğunu
        // təsdiqləyib — view isə telefonu yalnız status==='accepted' olan
        // təklif üçün render edir (digərlərində sıra var, amma göstərilmir).
        $offers = DB::conn()->prepare(
            "SELECT o.*, u.full_name, u.phone, u.jobs_done, u.cancel_count, u.vehicle_photo, u.created_at as driver_since,
                    vt.name_az as vt_name_az, vt.name_ru as vt_name_ru, vt.name_en as vt_name_en
             FROM offers o
             JOIN users u ON u.id = o.driver_id
             LEFT JOIN vehicle_types vt ON vt.id = u.vehicle_type_id
             WHERE o.listing_id = ? AND o.status IN ('pending','accepted')
             ORDER BY o.status = 'accepted' DESC, o.created_at DESC"
        );
        $offers->execute([$id]);

        $lastEventId = (int) (DB::conn()->query('SELECT MAX(id) m FROM sse_events')->fetch()['m'] ?? 0);

        View::render('customer/listing_show', [
            'pageTitle' => t('nav.my_listings'),
            'listing' => $listing,
            'photos' => $photos->fetchAll(),
            'offers' => $offers->fetchAll(),
            'lastEventId' => $lastEventId,
        ]);
    }

    public function remove(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];
        $listing = $this->fetchListingForCustomer($id, (int) Auth::id());
        if ($listing === null || $listing['status'] !== 'active') {
            header('Location: /musteri/elan/' . $id);
            return;
        }

        $stmt = DB::conn()->prepare("UPDATE listings SET status = 'removed', removed_by = 'customer' WHERE id = ?");
        $stmt->execute([$id]);
        $ev = DB::conn()->prepare('INSERT INTO listing_events (listing_id, event, actor_user_id) VALUES (?, "removed", ?)');
        $ev->execute([$id, Auth::id()]);
        Sse::publish('feed', 'listing_closed', ['listing_id' => $id]);

        header('Location: /musteri/elanlarim');
    }

    public function extend(array $params): void
    {
        Auth::requireRole('customer', '/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }
        $id = (int) $params['id'];
        $listing = $this->fetchListingForCustomer($id, (int) Auth::id());
        if ($listing === null || $listing['status'] !== 'active' || (int) $listing['extended'] === 1) {
            header('Location: /musteri/elan/' . $id);
            return;
        }

        $stmt = DB::conn()->prepare('UPDATE listings SET expires_at = DATE_ADD(expires_at, INTERVAL 72 HOUR), extended = 1 WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: /musteri/elan/' . $id);
    }

    /**
     * Yeni elan haqqında BÜTÜN təsdiqlənmiş (və bloklanmamış) sürücülərə push göndərir
     * (sahibkarın qərarı — əvvəlki route_subscriptions-a görə filtrləmə ləğv edildi;
     * qeyri-təsdiqli sürücü təklif verə bilmədiyi üçün push almasının mənası yoxdur,
     * bax Auth::isActiveDriver()).
     */
    private function notifyApprovedDrivers(int $listingId, array $fromLocation, array $toLocation): void
    {
        $stmt = DB::conn()->query(
            "SELECT id FROM users WHERE role = 'driver' AND driver_status = 'approved' AND is_blocked = 0"
        );
        $driverIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if ($driverIds === []) {
            return;
        }

        $route = \App\Core\Lang::field($fromLocation, 'name') . ' → ' . \App\Core\Lang::field($toLocation, 'name');
        WebPush::sendToUsersLocalized($driverIds, static fn () => [
            'title' => t('push.route_match_title'),
            'body' => $route,
            'url' => '/surucu/elan/' . $listingId,
        ]);
    }

    private function fetchListingForCustomer(int $id, int $customerId): ?array
    {
        $sql = 'SELECT ' . ListingRules::SELECT_SQL . ' ' . ListingRules::FROM_SQL . '
             WHERE l.id = ? AND l.customer_id = ? LIMIT 1';
        $stmt = DB::conn()->prepare($sql);
        $stmt->execute([$id, $customerId]);
        return $stmt->fetch() ?: null;
    }

    private function fetchOne(string $table, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = DB::conn()->prepare("SELECT * FROM {$table} WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Codes::publicListingCode();
            $stmt = DB::conn()->prepare('SELECT id FROM listings WHERE public_code = ? LIMIT 1');
            $stmt->execute([$code]);
        } while ($stmt->fetch() !== false);
        return $code;
    }
}
