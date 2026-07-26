<?php
declare(strict_types=1);

// Bir dəfəlik VAPID açar cütü generasiyası (Hissə 7.4). İşlətmə:
//   php app/generate_vapid_keys.php
// app/vapid_keys.php faylını yaradır (git-ə düşmür, .gitignore-a baxın).

require_once __DIR__ . '/webpush.php';

$target = __DIR__ . '/vapid_keys.php';
if (file_exists($target)) {
    fwrite(STDERR, "vapid_keys.php artıq mövcuddur — üzərinə yazılmadı.\n");
    exit(1);
}

$key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
$details = openssl_pkey_get_details($key);
openssl_pkey_export($key, $privateKeyPem);

$publicKeyRaw = ec_point_uncompressed($details['ec']['x'], $details['ec']['y']);

$content = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n"
    . "    'public_key_raw' => " . var_export(b64url_encode($publicKeyRaw), true) . ",\n"
    . "    'private_key' => " . var_export($privateKeyPem, true) . ",\n"
    . "    'subject' => 'mailto:admin@yuk.birlikde.biz',\n"
    . "];\n";

file_put_contents($target, $content);
echo "VAPID açarları yaradıldı: {$target}\n";
echo "Public key (frontend .env / config-ə lazım deyil — /api/v1/config vasitəsilə avtomatik ötürülür): "
    . b64url_encode($publicKeyRaw) . "\n";
