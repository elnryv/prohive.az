<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';

require_method('GET');

$user = session_user();
if ($user === null) {
    json_ok(['authenticated' => false]);
}

json_ok([
    'authenticated' => true,
    'user' => [
        'id' => (int) $user['id'],
        'role' => $user['role'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'avatar_path' => $user['avatar_path'],
    ],
]);
