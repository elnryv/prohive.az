<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Phone;
use App\Core\Upload;

/**
 * Profil şəkli/telefon nömrəsi dəyişikliyi — müştəri və sürücü üçün ortaq
 * (hər ikisi eyni `users` sütunlarını istifadə edir).
 */
final class ProfileController
{
    public function update(): void
    {
        Auth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $user = Auth::user();
        $redirectBase = $user['role'] === 'driver' ? '/surucu/profil' : '/musteri/profil';
        $errors = [];

        $newPhone = trim((string) ($_POST['phone'] ?? ''));
        if ($newPhone !== '') {
            $normalized = Phone::normalize($newPhone);
            if ($normalized === null) {
                $errors[] = 'nomre_yanlis';
            } elseif ($normalized !== $user['phone']) {
                $check = DB::conn()->prepare('SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1');
                $check->execute([$normalized, Auth::id()]);
                if ($check->fetch() !== false) {
                    $errors[] = 'nomre_movcuddur';
                } else {
                    DB::conn()->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$normalized, Auth::id()]);
                }
            }
        }

        if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $destDir = (string) Config::get('upload.profile_photo_dir');
            $result = Upload::storeImage($_FILES['profile_photo'], $destDir, (int) Config::get('upload.max_photo_bytes'), 800);
            if ($result['ok']) {
                $oldPhoto = $user['profile_photo'] ?? null;
                DB::conn()->prepare('UPDATE users SET profile_photo = ? WHERE id = ?')->execute([$result['filename'], Auth::id()]);
                if ($oldPhoto !== null && $oldPhoto !== '') {
                    @unlink(rtrim($destDir, '/') . '/' . $oldPhoto);
                }
            } else {
                $errors[] = 'sekil_' . $result['error'];
            }
        }

        if ($errors !== []) {
            header('Location: ' . $redirectBase . '?xeta=' . implode(',', $errors));
            return;
        }
        header('Location: ' . $redirectBase . '?netice=ok');
    }
}
