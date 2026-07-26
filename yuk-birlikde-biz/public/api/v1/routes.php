<?php
declare(strict_types=1);

// GET/POST/DELETE /api/v1/routes — sürücünün izlədiyi marşrutlar (Hissə 5.8).

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';
require_once __DIR__ . '/../../../app/validator.php';

maintenance_guard();
$user = require_auth();

if ($user['role'] !== 'driver') {
    json_error('FORBIDDEN', text('forbidden'), 403);
}

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('SELECT id, from_city, to_city, created_at FROM route_watches WHERE driver_id = :driver_id ORDER BY created_at');
    $stmt->execute(['driver_id' => $user['id']]);
    json_ok(['routes' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $body = request_body();
    $missing = require_fields($body, ['from_city', 'to_city']);
    if ($missing !== null) {
        json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM route_watches WHERE driver_id = :driver_id');
    $stmt->execute(['driver_id' => $user['id']]);
    if ((int) $stmt->fetchColumn() >= 5) {
        json_error('ROUTE_LIMIT', 'Maksimum 5 marşrut izləyə bilərsiniz.', 422);
    }

    $stmt = $db->prepare(
        'INSERT INTO route_watches (driver_id, from_city, to_city) VALUES (:driver_id, :from_city, :to_city)'
    );
    $stmt->execute([
        'driver_id' => $user['id'],
        'from_city' => trim((string) $body['from_city']),
        'to_city' => trim((string) $body['to_city']),
    ]);

    json_ok(['id' => (int) $db->lastInsertId()], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    csrf_validate();
    $body = request_body();
    $missing = require_fields($body, ['id']);
    if ($missing !== null) {
        json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
    }

    $db->prepare('DELETE FROM route_watches WHERE id = :id AND driver_id = :driver_id')
        ->execute(['id' => (int) $body['id'], 'driver_id' => $user['id']]);

    json_ok();
}

json_error('METHOD_NOT_ALLOWED', 'Bu metod dəstəklənmir.', 405);
