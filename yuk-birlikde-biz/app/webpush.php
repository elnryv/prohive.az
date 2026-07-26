<?php
declare(strict_types=1);

// Native Web Push implementasiyası — kənar asılılıqsız (Hissə 7.4).
// RFC 8291 (aes128gcm mesaj şifrələnməsi) + RFC 8292 (VAPID) əl ilə tətbiq olunur.

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string
{
    $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + (4 - strlen($data) % 4), '=');
    return base64_decode($padded);
}

function ec_point_uncompressed(string $x, string $y): string
{
    return "\x04" . str_pad($x, 32, "\x00", STR_PAD_LEFT) . str_pad($y, 32, "\x00", STR_PAD_LEFT);
}

// Xam 65 baytlıq uncompressed EC nöqtəsini openssl_pkey_derive()-in qəbul edə
// biləcəyi PEM SubjectPublicKeyInfo formatına çevirir (P-256).
function ec_raw_point_to_pem(string $raw): string
{
    $der = static fn (int $len): string => $len < 128
        ? chr($len)
        : chr(0x80 | strlen(ltrim(pack('N', $len), "\x00"))) . ltrim(pack('N', $len), "\x00");

    $oidEcPublicKey = "\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01";
    $oidPrime256v1 = "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07";
    $algId = "\x30" . $der(strlen($oidEcPublicKey . $oidPrime256v1)) . $oidEcPublicKey . $oidPrime256v1;
    $bitString = "\x03" . $der(strlen($raw) + 1) . "\x00" . $raw;
    $spki = "\x30" . $der(strlen($algId . $bitString)) . $algId . $bitString;

    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function der_signature_to_raw(string $der): string
{
    $offset = (ord($der[1]) & 0x80) ? 2 + (ord($der[1]) & 0x7F) : 2;

    $readInt = static function (string $der, int &$offset): string {
        $offset++; // 0x02 INTEGER tag
        $len = ord($der[$offset]);
        $offset++;
        $val = ltrim(substr($der, $offset, $len), "\x00");
        $offset += $len;
        return str_pad($val, 32, "\x00", STR_PAD_LEFT);
    };

    $r = $readInt($der, $offset);
    $s = $readInt($der, $offset);

    return $r . $s;
}

function vapid_build_jwt(string $audience, string $subject, $privateKey): string
{
    $header = b64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = b64url_encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 12 * 3600,
        'sub' => $subject,
    ]));
    $signingInput = "{$header}.{$claims}";

    openssl_sign($signingInput, $derSignature, $privateKey, OPENSSL_ALGO_SHA256);

    return $signingInput . '.' . b64url_encode(der_signature_to_raw($derSignature));
}

// Mesajı RFC 8291 aes128gcm sxeminə uyğun şifrələyir, HTTP body-yə hazır bayt axını qaytarır.
function webpush_encrypt_payload(string $payload, string $p256dhB64url, string $authB64url): string
{
    $uaPubRaw = b64url_decode($p256dhB64url);
    $authSecret = b64url_decode($authB64url);

    $asKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $asDetails = openssl_pkey_get_details($asKey);
    $asPubRaw = ec_point_uncompressed($asDetails['ec']['x'], $asDetails['ec']['y']);

    $sharedSecret = openssl_pkey_derive(ec_raw_point_to_pem($uaPubRaw), $asKey);
    if ($sharedSecret === false) {
        throw new RuntimeException('ECDH derivation failed');
    }

    $keyInfo = "WebPush: info\x00" . $uaPubRaw . $asPubRaw;
    $ikm = hash_hkdf('sha256', $sharedSecret, 32, $keyInfo, $authSecret);

    $salt = random_bytes(16);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

    $plaintext = $payload . "\x02";
    $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('AES-GCM encryption failed');
    }

    $header = $salt . pack('N', 4096) . chr(strlen($asPubRaw)) . $asPubRaw;

    return $header . $ciphertext . $tag;
}

// Push abunəliyinə mesaj göndərir. Nəticə: true (uğurlu), false (uğursuz, lakin
// abunəlik hələ etibarlı ola bilər) — 404/410 halında $deadCallback çağırılır ki,
// çağıran ölü abunəliyi silsin (Hissə 7.4: "Ölü subscription-lar avtomatik silinir").
function send_web_push(array $subscription, string $payloadJson, ?callable $onDead = null): bool
{
    $vapid = require __DIR__ . '/vapid_keys.php';

    $endpoint = $subscription['endpoint'];
    $host = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

    $jwt = vapid_build_jwt($host, $vapid['subject'], $vapid['private_key']);
    $body = webpush_encrypt_payload($payloadJson, $subscription['p256dh'], $subscription['auth']);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Authorization: vapid t=' . $jwt . ', k=' . $vapid['public_key_raw'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (($status === 404 || $status === 410) && $onDead !== null) {
        $onDead();
    }

    return $status >= 200 && $status < 300;
}
