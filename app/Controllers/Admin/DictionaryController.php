<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\DB;

/** Lüğət CRUD-ları (bölmə 9.5): kateqoriyalar, lokasiyalar, maşın növləri. */
final class DictionaryController
{
    private const TABLES = ['categories', 'locations', 'vehicle_types'];

    public function add(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $table = $this->validTable($params['table']);

        $nameAz = trim((string) ($_POST['name_az'] ?? ''));
        $nameRu = trim((string) ($_POST['name_ru'] ?? ''));
        $nameEn = trim((string) ($_POST['name_en'] ?? ''));
        if ($nameAz === '' || $nameRu === '' || $nameEn === '') {
            header('Location: /parametrler?xeta=ad_lazimdir');
            return;
        }

        if ($table === 'categories') {
            $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($nameEn));
            $stmt = DB::conn()->prepare(
                'INSERT INTO categories (slug, name_az, name_ru, name_en, icon, hint_az, hint_ru, hint_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $slug, $nameAz, $nameRu, $nameEn,
                trim((string) ($_POST['icon'] ?? '📦')) ?: '📦',
                trim((string) ($_POST['hint_az'] ?? '')) ?: null,
                trim((string) ($_POST['hint_ru'] ?? '')) ?: null,
                trim((string) ($_POST['hint_en'] ?? '')) ?: null,
            ]);
        } elseif ($table === 'locations') {
            $stmt = DB::conn()->prepare('INSERT INTO locations (name_az, name_ru, name_en, is_baku) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nameAz, $nameRu, $nameEn, !empty($_POST['is_baku']) ? 1 : 0]);
        } else {
            $stmt = DB::conn()->prepare('INSERT INTO vehicle_types (name_az, name_ru, name_en) VALUES (?, ?, ?)');
            $stmt->execute([$nameAz, $nameRu, $nameEn]);
        }

        AdminAuth::log('dictionary_add', $table, (int) DB::conn()->lastInsertId(), ['name_az' => $nameAz]);
        header('Location: /parametrler');
    }

    public function toggle(array $params): void
    {
        AdminAuth::requireLogin('/giris');
        $this->assertCsrf();
        $table = $this->validTable($params['table']);
        $id = (int) $params['id'];

        $stmt = DB::conn()->prepare("SELECT is_active FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            header('Location: /parametrler');
            return;
        }
        $newVal = (int) $row['is_active'] === 1 ? 0 : 1;
        DB::conn()->prepare("UPDATE {$table} SET is_active = ? WHERE id = ?")->execute([$newVal, $id]);

        AdminAuth::log('dictionary_toggle', $table, $id, ['is_active' => $newVal]);
        header('Location: /parametrler');
    }

    private function validTable(string $table): string
    {
        if (!in_array($table, self::TABLES, true)) {
            http_response_code(400);
            echo 'Yanlış cədvəl';
            exit;
        }
        return $table;
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
