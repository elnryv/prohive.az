<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/validator.php';

maintenance_guard();
$user = require_auth();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $body = request_body();
    $missing = require_fields($body, ['endpoint']);
    if ($missing !== null || !isset($body['keys']['p256dh'], $body['keys']['auth'])) {
        json_error('FIELD_REQUIRED', 'Abunəlik məlumatları natamamdır.', 422);
    }

    $endpoint = (string) $body['endpoint'];
    $stmt = $db->prepare('SELECT id FROM push_subscriptions WHERE user_id = :user_id AND endpoint = :endpoint');
    $stmt->execute(['user_id' => $user['id'], 'endpoint' => $endpoint]);

    if ($stmt->fetch() === false) {
        $db->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (:user_id, :endpoint, :p256dh, :auth)'
        )->execute([
            'user_id' => $user['id'],
            'endpoint' => $endpoint,
            'p256dh' => $body['keys']['p256dh'],
            'auth' => $body['keys']['auth'],
        ]);
    }

    json_ok([], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    csrf_validate();
    $body = request_body();
    if (!isset($body['endpoint'])) {
        json_error('FIELD_REQUIRED', 'Endpoint tələb olunur.', 422);
    }

    $db->prepare('DELETE FROM push_subscriptions WHERE user_id = :user_id AND endpoint = :endpoint')
        ->execute(['user_id' => $user['id'], 'endpoint' => (string) $body['endpoint']]);

    json_ok();
}

json_error('METHOD_NOT_ALLOWED', 'Bu metod dəstəklənmir.', 405);
