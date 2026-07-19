<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/auth.php';

require_method('GET');
$user = require_auth();

$stmt = db()->prepare(
    'SELECT id, type, title, body, link, is_read, created_at FROM notifications
     WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 100'
);
$stmt->execute(['user_id' => $user['id']]);

json_ok(['notifications' => $stmt->fetchAll()]);
