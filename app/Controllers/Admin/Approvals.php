<?php
declare(strict_types=1);

/** Təsdiq növbəsi (bölmə 9.2): pending evlər + is_approved=0 yeni fotolar. */
final class Approvals
{
    public function index(): void
    {
        AdminAuth::requireLogin();

        View::render('admin/approvals', [
            'title' => 'Təsdiq növbəsi — Admin',
            'houses' => HouseRepository::pendingHouses(),
            'photos' => HouseRepository::pendingPhotos(),
        ], 'layout');
    }

    public function show(array $params): void
    {
        AdminAuth::requireLogin();
        $house = $this->houseOrDie($params);

        View::render('admin/approval_detail', [
            'title' => View::e($house['title']) . ' — Təsdiq — Admin',
            'house' => $house,
            'photos' => HouseRepository::allPhotos((int) $house['id']),
            'amenities' => HouseRepository::amenities((int) $house['id']),
        ], 'layout');
    }

    public function approve(array $params): void
    {
        AdminAuth::requireLogin();
        $house = $this->houseOrDie($params);
        $this->verifyCsrfOrDie((int) $house['id']);

        HouseRepository::approve((int) $house['id']);
        AdminLog::write('house.approve', (int) $house['id'], $house['title']);

        header('Location: /tesdiq?tesdiqlendi=1');
        exit;
    }

    public function reject(array $params): void
    {
        AdminAuth::requireLogin();
        $house = $this->houseOrDie($params);
        $this->verifyCsrfOrDie((int) $house['id']);

        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '') {
            header("Location: /tesdiq/{$house['id']}?xeta=sebeb");
            exit;
        }

        HouseRepository::reject((int) $house['id'], $reason);
        AdminLog::write('house.reject', (int) $house['id'], $reason);

        header('Location: /tesdiq?geri_gonderildi=1');
        exit;
    }

    public function approvePhoto(array $params): void
    {
        AdminAuth::requireLogin();
        $photoId = (int) ($params['photoId'] ?? 0);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header('Location: /tesdiq');
            exit;
        }

        HouseRepository::approvePhoto($photoId);
        AdminLog::write('photo.approve', $photoId);

        header('Location: /tesdiq?foto_tesdiqlendi=1');
        exit;
    }

    public function declinePhoto(array $params): void
    {
        AdminAuth::requireLogin();
        $photoId = (int) ($params['photoId'] ?? 0);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header('Location: /tesdiq');
            exit;
        }

        $photo = HouseRepository::findPhotoById($photoId);
        if ($photo !== null) {
            Upload::deleteFile((int) $photo['house_id'], $photo['filename']);
            HouseRepository::deletePhotoById($photoId);
            AdminLog::write('photo.decline', $photoId, 'house_id=' . $photo['house_id']);
        }

        header('Location: /tesdiq?foto_rededildi=1');
        exit;
    }

    private function houseOrDie(array $params): array
    {
        $house = HouseRepository::findByIdAdmin((int) ($params['id'] ?? 0));
        if ($house === null) {
            http_response_code(404);
            View::render('admin/placeholder', ['title' => '404 — Admin']);
            exit;
        }
        return $house;
    }

    private function verifyCsrfOrDie(int $houseId): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header("Location: /tesdiq/{$houseId}");
            exit;
        }
    }
}
