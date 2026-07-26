<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/response.php';
require_once __DIR__ . '/../../../../app/db.php';
require_once __DIR__ . '/../../../../app/settings.php';
require_once __DIR__ . '/../../../../app/auth.php';
require_once __DIR__ . '/../../../../app/csrf.php';
require_once __DIR__ . '/../../../../app/ratelimit.php';
require_once __DIR__ . '/../../../../app/validator.php';
require_once __DIR__ . '/../../../../app/texts.php';

require_method('POST');
maintenance_guard();
csrf_validate();
rate_limit_guard('auth_ip_' . client_ip(), 5, 900);

$body = request_body();

$missing = require_fields($body, ['phone', 'pin', 'role', 'first_name', 'last_name', 'consent']);
if ($missing !== null) {
    json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
}

if ($body['consent'] !== true) {
    json_error('CONSENT_REQUIRED', text('consent_required'), 422);
}

$role = $body['role'];
if (!in_array($role, ['customer', 'driver'], true)) {
    json_error('INVALID_ROLE', 'Rol yanlışdır.', 422);
}

if ($role === 'customer' && !settings_get_bool('reg_customer_enabled', true)) {
    json_error('REGISTRATION_CLOSED', text('registration_closed'), 403);
}
if ($role === 'driver' && !settings_get_bool('reg_driver_enabled', true)) {
    json_error('REGISTRATION_CLOSED', text('registration_closed'), 403);
}

$phone = preg_replace('/\D/', '', (string) $body['phone']);
if (!preg_match('/^994\d{9}$/', $phone)) {
    json_error('PHONE_INCOMPLETE', text('phone_incomplete'), 422);
}

$pinLength = settings_get_int('pin_length', 4);
if (!is_valid_pin((string) $body['pin'], $pinLength)) {
    json_error('PIN_INVALID', "PIN {$pinLength} rəqəmli olmalıdır.", 422);
}

$firstName = trim((string) $body['first_name']);
$lastName = trim((string) $body['last_name']);
if ($firstName === '') {
    json_error('NAME_REQUIRED', text('name_required'), 422);
}
if ($lastName === '') {
    json_error('SURNAME_REQUIRED', text('surname_required'), 422);
}

$vehicleId = null;
$vehicleSizeId = null;
$vehicleOther = null;
if ($role === 'driver') {
    $missing = require_fields($body, ['vehicle_id', 'vehicle_size_id']);
    if ($missing !== null) {
        json_error('FIELD_REQUIRED', "Sahə tələb olunur: {$missing}", 422);
    }
    $vehicleId = (int) $body['vehicle_id'];
    $vehicleSizeId = (int) $body['vehicle_size_id'];
    $vehicleOther = isset($body['vehicle_other']) ? trim((string) $body['vehicle_other']) : null;
}

$db = db();

$stmt = $db->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
$stmt->execute(['phone' => $phone]);
if ($stmt->fetch() !== false) {
    json_error('PHONE_EXISTS', text('phone_exists'), 409);
}

$db->beginTransaction();
try {
    $stmt = $db->prepare(
        'INSERT INTO users (role, phone, pin_hash, first_name, last_name)
         VALUES (:role, :phone, :pin_hash, :first_name, :last_name)'
    );
    $stmt->execute([
        'role' => $role,
        'phone' => $phone,
        'pin_hash' => password_hash((string) $body['pin'], PASSWORD_ARGON2ID),
        'first_name' => $firstName,
        'last_name' => $lastName,
    ]);
    $userId = (int) $db->lastInsertId();

    if ($role === 'driver') {
        $stmt = $db->prepare(
            'INSERT INTO drivers (user_id, vehicle_id, vehicle_other, vehicle_size_id)
             VALUES (:user_id, :vehicle_id, :vehicle_other, :vehicle_size_id)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
            'vehicle_other' => $vehicleOther,
            'vehicle_size_id' => $vehicleSizeId,
        ]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}

session_create($userId);

json_ok(['user' => [
    'id' => $userId,
    'role' => $role,
    'first_name' => $firstName,
    'last_name' => $lastName,
]], 201);
