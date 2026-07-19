<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';

require_method('POST');
csrf_validate();
$user = require_auth();

db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0')
    ->execute(['user_id' => $user['id']]);

json_ok();
