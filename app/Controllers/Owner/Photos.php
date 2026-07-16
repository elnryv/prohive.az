<?php
declare(strict_types=1);

/** Foto/video AJAX idarəetməsi (7.3 addım 4, 12.2). */
final class Photos
{
    private const MAX_PHOTOS = 20;

    public function upload(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $house = $this->ownedHouseOrJsonError($params);
        if ($house === null) {
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'no_file']);
            return;
        }

        $file = $_FILES['file'];
        $houseId = (int) $house['id'];
        $existing = HouseRepository::allPhotos($houseId);
        $photoCount = count(array_filter($existing, static fn ($p) => !$p['is_video']));
        $hasVideo = count(array_filter($existing, static fn ($p) => (bool) $p['is_video'])) > 0;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'upload_error']);
            return;
        }

        $mime = Upload::detectMime($file['tmp_name']);
        $isVideo = str_starts_with($mime, 'video/');

        try {
            if ($isVideo) {
                if ($hasVideo) {
                    throw new RuntimeException('Artıq 1 video yüklənib, əvvəlkini silin.');
                }
                $filename = Upload::storeVideo($file, $houseId);
            } else {
                if ($photoCount >= self::MAX_PHOTOS) {
                    throw new RuntimeException('Maksimum ' . self::MAX_PHOTOS . ' foto yükləyə bilərsiniz.');
                }
                $filename = Upload::storeImage($file, $houseId);
            }
        } catch (RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            return;
        }

        $makeCover = !$isVideo && $photoCount === 0;
        $photoId = HouseRepository::addPhoto($houseId, $filename, $isVideo, $makeCover);

        echo json_encode([
            'ok' => true,
            'id' => $photoId,
            'url' => "/uploads/houses/{$houseId}/{$filename}",
            'is_video' => $isVideo,
            'is_cover' => $makeCover,
        ]);
    }

    public function delete(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $house = $this->ownedHouseOrJsonError($params);
        if ($house === null) {
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        $houseId = (int) $house['id'];
        $photoId = (int) ($params['photoId'] ?? 0);
        $photo = HouseRepository::findPhotoOwned($photoId, $houseId);
        if ($photo === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return;
        }

        Upload::deleteFile($houseId, $photo['filename']);
        HouseRepository::deletePhoto($photoId, $houseId);

        if ((bool) $photo['is_cover']) {
            $remaining = HouseRepository::allPhotos($houseId);
            foreach ($remaining as $p) {
                if (!$p['is_video']) {
                    HouseRepository::setCoverPhoto((int) $p['id'], $houseId);
                    break;
                }
            }
        }

        echo json_encode(['ok' => true]);
    }

    public function setCover(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $house = $this->ownedHouseOrJsonError($params);
        if ($house === null) {
            return;
        }
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        $houseId = (int) $house['id'];
        $photoId = (int) ($params['photoId'] ?? 0);
        $photo = HouseRepository::findPhotoOwned($photoId, $houseId);
        if ($photo === null || (bool) $photo['is_video']) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return;
        }

        HouseRepository::setCoverPhoto($photoId, $houseId);
        echo json_encode(['ok' => true]);
    }

    private function ownedHouseOrJsonError(array $params): ?array
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'auth']);
            return null;
        }
        $owner = Auth::user();
        $house = HouseRepository::findOwnedById((int) ($params['id'] ?? 0), (int) $owner['id']);
        if ($house === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return null;
        }
        return $house;
    }
}
