<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/csrf.php';

maintenance_guard();
$user = require_auth();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $profile = [
        'id' => (int) $user['id'],
        'role' => $user['role'],
        'phone' => $user['phone'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'avatar_path' => $user['avatar_path'],
        'notify_offers' => (bool) $user['notify_offers'],
        'notify_status' => (bool) $user['notify_status'],
        'notify_system' => (bool) $user['notify_system'],
    ];

    if ($user['role'] === 'driver') {
        $stmt = $db->prepare(
            'SELECT d.vehicle_id, d.vehicle_other, d.vehicle_size_id, d.rating_avg, d.rating_count, d.completed_count,
                    v.name AS vehicle_name, vs.code AS vehicle_size_code
             FROM drivers d
             JOIN vehicles v ON v.id = d.vehicle_id
             JOIN vehicle_sizes vs ON vs.id = d.vehicle_size_id
             WHERE d.user_id = :id'
        );
        $stmt->execute(['id' => $user['id']]);
        $profile['driver'] = $stmt->fetch();
    }

    json_ok(['profile' => $profile]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    csrf_validate();
    $body = request_body();

    if (isset($body['first_name']) || isset($body['last_name'])) {
        $firstName = trim((string) ($body['first_name'] ?? $user['first_name']));
        $lastName = trim((string) ($body['last_name'] ?? $user['last_name']));
        if ($firstName === '' || $lastName === '') {
            json_error('FIELD_REQUIRED', 'Ad və soyad boş ola bilməz.', 422);
        }
        $db->prepare('UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id')
            ->execute(['first_name' => $firstName, 'last_name' => $lastName, 'id' => $user['id']]);
    }

    foreach (['notify_offers', 'notify_status', 'notify_system'] as $field) {
        if (isset($body[$field])) {
            $db->prepare("UPDATE users SET {$field} = :value WHERE id = :id")
                ->execute(['value' => $body[$field] ? 1 : 0, 'id' => $user['id']]);
        }
    }

    if ($user['role'] === 'driver' && (isset($body['vehicle_id']) || isset($body['vehicle_size_id']))) {
        $fields = [];
        $params = ['id' => $user['id']];
        if (isset($body['vehicle_id'])) {
            $fields[] = 'vehicle_id = :vehicle_id';
            $params['vehicle_id'] = (int) $body['vehicle_id'];
        }
        if (isset($body['vehicle_size_id'])) {
            $fields[] = 'vehicle_size_id = :vehicle_size_id';
            $params['vehicle_size_id'] = (int) $body['vehicle_size_id'];
        }
        if (array_key_exists('vehicle_other', $body)) {
            $fields[] = 'vehicle_other = :vehicle_other';
            $params['vehicle_other'] = $body['vehicle_other'];
        }
        $db->prepare('UPDATE drivers SET ' . implode(', ', $fields) . ' WHERE user_id = :id')->execute($params);
    }

    json_ok();
}

json_error('METHOD_NOT_ALLOWED', 'Bu metod dəstəklənmir.', 405);
