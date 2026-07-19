<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

$body = request_body();
if (!isset($body['pin']) || !password_verify((string) $body['pin'], $user['pin_hash'])) {
    json_error('PIN_WRONG', 'PIN yanlışdır.', 401);
}

$db = db();
$db->prepare('UPDATE users SET status = "deleted" WHERE id = :id')->execute(['id' => $user['id']]);
$db->prepare('DELETE FROM sessions WHERE user_id = :id')->execute(['id' => $user['id']]);

app_session_destroy();

json_ok();
