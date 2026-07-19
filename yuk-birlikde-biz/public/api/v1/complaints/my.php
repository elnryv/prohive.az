<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';

require_method('GET');
$user = require_auth();

$stmt = db()->prepare(
    'SELECT id, subject, message, status, admin_reply, created_at, updated_at
     FROM complaints WHERE user_id = :user_id ORDER BY created_at DESC'
);
$stmt->execute(['user_id' => $user['id']]);

json_ok(['complaints' => $stmt->fetchAll()]);
