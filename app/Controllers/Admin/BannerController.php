<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Upload;
use App\Core\View;

/** Bannerlər (bölmə: admin naviqasiya v2 əlavəsi) — müştəri/sürücü ekranlarında başlıqdan aşağıda sürüşən reklam bannerləri. */
final class BannerController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');

        $banners = DB::conn()->query('SELECT * FROM banners ORDER BY sort_order, id')->fetchAll();

        View::render('admin/banners', [
            'pageTitle' => 'Bannerlər',
            'banners' => $banners,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();

        if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            header('Location: /bannerler?xeta=sekil_lazimdir');
            return;
        }

        $destDir = (string) Config::get('upload.banner_dir');
        $result = Upload::storeImage($_FILES['image'], $destDir, (int) Config::get('upload.max_photo_bytes'), 1600);
        if (!$result['ok']) {
            header('Location: /bannerler?xeta=sekil_' . $result['error']);
            return;
        }

        $stmt = DB::conn()->prepare(
            'INSERT INTO banners (image, title_az, title_ru, title_en, link_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $result['filename'],
            trim((string) ($_POST['title_az'] ?? '')) ?: null,
            trim((string) ($_POST['title_ru'] ?? '')) ?: null,
            trim((string) ($_POST['title_en'] ?? '')) ?: null,
            trim((string) ($_POST['link_url'] ?? '')) ?: null,
            (int) ($_POST['sort_order'] ?? 0),
        ]);

        AdminAuth::log('banner_create', 'banner', (int) DB::conn()->lastInsertId());
        header('Location: /bannerler');
    }

    public function toggle(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT is_active FROM banners WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            header('Location: /bannerler');
            return;
        }
        $newVal = (int) $row['is_active'] === 1 ? 0 : 1;
        DB::conn()->prepare('UPDATE banners SET is_active = ? WHERE id = ?')->execute([$newVal, $id]);

        AdminAuth::log('banner_toggle', 'banner', $id, ['is_active' => $newVal]);
        header('Location: /bannerler');
    }

    public function delete(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare('SELECT image FROM banners WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row !== false) {
            $destDir = (string) Config::get('upload.banner_dir');
            @unlink(rtrim($destDir, '/') . '/' . $row['image']);
            DB::conn()->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
            AdminAuth::log('banner_delete', 'banner', $id);
        }

        header('Location: /bannerler');
    }

    private function assertCsrf(): void
    {
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            exit;
        }
    }
}
