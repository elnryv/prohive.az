<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/image.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

if (!isset($_FILES['avatar'])) {
    json_error('FIELD_REQUIRED', 'Şəkil tapılmadı.', 422);
}

try {
    $filename = save_uploaded_avatar($_FILES['avatar'], __DIR__ . '/../../../../storage/uploads/avatars');
} catch (RuntimeException $e) {
    json_error('UPLOAD_FAILED', 'Şəkil yüklənə bilmədi.', 422);
}

$path = 'avatars/' . $filename;
db()->prepare('UPDATE users SET avatar_path = :path WHERE id = :id')
    ->execute(['path' => $path, 'id' => $user['id']]);

json_ok(['avatar_path' => $path]);
