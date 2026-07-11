<?php

declare(strict_types=1);

/**
 * CLI: php database/generate_vapid_keys.php
 * Web Push üçün VAPID EC (P-256) açar cütü yaradır (bax CLAUDE.md bölmə 9.3.3,
 * PUSH-9.3.3: "bir dəfə generasiya"). Çıxışı .env-də VAPID_PUBLIC_KEY/
 * VAPID_PRIVATE_KEY dəyərləri kimi istifadə edin. Bu skript heç nəyi fayla
 * yazmır — nəticəni terminala çap edir, admin özü .env-ə köçürür.
 */

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$config = [
    'curve_name' => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
];

$resource = openssl_pkey_new($config);
if ($resource === false) {
    fwrite(STDERR, "EC açar cütü yaradıla bilmədi (OpenSSL): " . openssl_error_string() . "\n");
    exit(1);
}

$details = openssl_pkey_get_details($resource);
if ($details === false || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
    fwrite(STDERR, "EC açar detalları oxuna bilmədi.\n");
    exit(1);
}

$x = $details['ec']['x'];
$y = $details['ec']['y'];
$d = $details['ec']['d'];

// Uncompressed point format: 0x04 || X(32 byte) || Y(32 byte)
$publicKeyRaw = "\x04" . str_pad($x, 32, "\x00", STR_PAD_LEFT) . str_pad($y, 32, "\x00", STR_PAD_LEFT);
$privateKeyRaw = str_pad($d, 32, "\x00", STR_PAD_LEFT);

echo "VAPID_PUBLIC_KEY=" . base64UrlEncode($publicKeyRaw) . "\n";
echo "VAPID_PRIVATE_KEY=" . base64UrlEncode($privateKeyRaw) . "\n";
echo "\nBu dəyərləri .env faylına köçürün. Açarlar bir dəfə yaradılır və dəyişdirilmir\n";
echo "(dəyişdirilsə, əvvəlki bütün push abunəlikləri etibarsız olar).\n";
