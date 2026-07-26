<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/validator.php';

require_method('POST');
maintenance_guard();
csrf_validate();
$user = require_auth();

$body = request_body();
$missing = require_fields($body, ['subject', 'message']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

$stmt = db()->prepare('INSERT INTO complaints (user_id, subject, message) VALUES (:user_id, :subject, :message)');
$stmt->execute([
    'user_id' => $user['id'],
    'subject' => substr((string) $body['subject'], 0, 120),
    'message' => (string) $body['message'],
]);

json_ok([], 201);
