<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';
require_once __DIR__ . '/../../../app/settings.php';
require_once __DIR__ . '/../../../app/csrf.php';

require_method('GET');

csrf_ensure_token();

$db = db();
$operators = $db->query('SELECT prefix FROM operators WHERE is_active = 1 ORDER BY sort')->fetchAll(PDO::FETCH_COLUMN);
$vehicles = $db->query('SELECT id, name FROM vehicles WHERE is_active = 1 ORDER BY sort')->fetchAll();
$vehicleSizes = $db->query('SELECT id, code, dimensions FROM vehicle_sizes WHERE is_active = 1 ORDER BY sort')->fetchAll();
$cargoTypes = $db->query('SELECT id, name FROM cargo_types WHERE is_active = 1 ORDER BY sort')->fetchAll();

json_ok([
    'site_name' => settings_get('site_name', 'Yük Birlikdə'),
    'pin_length' => settings_get_int('pin_length', 4),
    'reg_customer_enabled' => settings_get_bool('reg_customer_enabled', true),
    'reg_driver_enabled' => settings_get_bool('reg_driver_enabled', true),
    'maintenance_mode' => settings_get_bool('maintenance_mode', false),
    'subscription' => [
        'mode' => settings_get('subscription_mode', 'free'),
        'price' => (float) settings_get('subscription_price', '0'),
        'currency' => settings_get('subscription_currency', 'AZN'),
        'days' => settings_get_int('subscription_days', 30),
    ],
    'whatsapp_number' => settings_get('whatsapp_number', ''),
    'contact_phone' => settings_get('contact_phone', ''),
    'copyright' => settings_get('copyright', ''),
    'pwa_prompt_enabled' => settings_get_bool('pwa_prompt_enabled', true),
    'vapid_public_key' => file_exists(__DIR__ . '/../../../app/vapid_keys.php')
        ? (require __DIR__ . '/../../../app/vapid_keys.php')['public_key_raw']
        : null,
    'operators' => $operators,
    'vehicles' => $vehicles,
    'vehicle_sizes' => $vehicleSizes,
    'cargo_types' => $cargoTypes,
]);
