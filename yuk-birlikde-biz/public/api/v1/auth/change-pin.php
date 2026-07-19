<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/validator.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

$body = request_body();
$missing = require_fields($body, ['old_pin', 'new_pin']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

if (!password_verify((string) $body['old_pin'], $user['pin_hash'])) {
    json_error('PIN_WRONG', 'Köhnə PIN yanlışdır.', 401);
}

$pinLength = settings_get_int('pin_length', 4);
if (!is_valid_pin((string) $body['new_pin'], $pinLength)) {
    json_error('PIN_INVALID', "PIN {$pinLength} rəqəmli olmalıdır.", 422);
}

db()->prepare('UPDATE users SET pin_hash = :pin_hash WHERE id = :id')
    ->execute(['pin_hash' => password_hash((string) $body['new_pin'], PASSWORD_ARGON2ID), 'id' => $user['id']]);

json_ok();
