<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal native Web Push (VAPID) implementasiyası — Composer yoxdur.
 * RFC 8291 (aes128gcm payload encryption) + RFC 8292 (VAPID JWT, ES256).
 *
 * Riyaziyyat üçün ext-gmp/ext-bcmath TƏLƏB OLUNMUR: ECDH paylaşılan sirri
 * PHP-nin öz openssl_pkey_derive() funksiyası ilə hesablanır (PHP 8.1+,
 * ext-openssl daxilində standart). Bütün kriptoqrafiya addımları (ECDH →
 * HKDF-SHA256 → AES-128-GCM) əl ilə RFC-8291/8188 test ssenarisi üzərində
 * encrypt→decrypt round-trip ilə yoxlanılıb (bax PROGRESS.md FAZA 4 qeydi).
 */
final class WebPush
{
    private const EC_SPKI_PREFIX_HEX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    // ------------------------------------------------------------------
    // Base64url
    // ------------------------------------------------------------------
    public static function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return (string) base64_decode($data);
    }

    // ------------------------------------------------------------------
    // VAPID keygen (cron/vapid_keygen.php tərəfindən çağırılır)
    // ------------------------------------------------------------------

    /** @return array{public:string, private:string} base64url dəyərlər (settings-ə yazılır) */
    public static function generateVapidKeys(): array
    {
        $priv = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($priv === false) {
            throw new \RuntimeException('EC açar cütü yaradıla bilmədi (openssl).');
        }
        $details = openssl_pkey_get_details($priv);
        $publicRaw = self::rawPointFromCoords($details['ec']['x'], $details['ec']['y']);

        openssl_pkey_export($priv, $pem);
        $der = self::pemToDer($pem);

        return [
            'public' => self::b64urlEncode($publicRaw),
            'private' => self::b64urlEncode($der),
        ];
    }

    private static function rawPointFromCoords(string $x, string $y): string
    {
        $x = str_pad($x, 32, "\0", STR_PAD_LEFT);
        $y = str_pad($y, 32, "\0", STR_PAD_LEFT);
        return "\x04" . $x . $y;
    }

    private static function pemToDer(string $pem): string
    {
        $lines = explode("\n", trim($pem));
        $lines = array_filter($lines, static fn ($l) => !str_starts_with($l, '-----'));
        return (string) base64_decode(implode('', $lines));
    }

    private static function derToPem(string $der, string $label): string
    {
        return "-----BEGIN {$label}-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END {$label}-----\n";
    }

    private static function loadVapidPrivateKey(): mixed
    {
        $stored = Settings::get('vapid_private', '');
        if ($stored === null || $stored === '') {
            return null;
        }
        $der = self::b64urlDecode($stored);
        $pem = self::derToPem($der, 'PRIVATE KEY');
        $key = openssl_pkey_get_private($pem);
        return $key !== false ? $key : null;
    }

    public static function isConfigured(): bool
    {
        return Settings::get('vapid_public', '') !== '' && Settings::get('vapid_private', '') !== '';
    }

    // ------------------------------------------------------------------
    // Public key reconstruction (subscriber p256dh, VAPID public üçün)
    // ------------------------------------------------------------------
    private static function publicKeyFromRawPoint(string $rawPoint): mixed
    {
        $der = hex2bin(self::EC_SPKI_PREFIX_HEX) . $rawPoint;
        $pem = self::derToPem($der, 'PUBLIC KEY');
        $key = openssl_pkey_get_public($pem);
        return $key !== false ? $key : null;
    }

    // ------------------------------------------------------------------
    // HKDF (HMAC-SHA256 əsaslı, RFC 5869)
    // ------------------------------------------------------------------
    private static function hkdfExtract(string $salt, string $ikm): string
    {
        return hash_hmac('sha256', $ikm, $salt, true);
    }

    private static function hkdfExpand(string $prk, string $info, int $len): string
    {
        // len <= 32 (hash uzunluğu) olduğu üçün tək HMAC bloku kifayətdir (RFC 8291 kontekstində həmişə belədir).
        return substr(hash_hmac('sha256', $info . chr(1), $prk, true), 0, $len);
    }

    // ------------------------------------------------------------------
    // ECDSA DER <-> raw (JOSE) çevrilməsi
    // ------------------------------------------------------------------
    private static function derToRawSignature(string $der, int $partLen = 32): string
    {
        $offset = 1; // 0x30 SEQUENCE tag
        $seqLen = ord($der[$offset]);
        $offset++;
        if ($seqLen & 0x80) {
            $offset += $seqLen & 0x7f;
        }
        $readInt = static function () use ($der, &$offset, $partLen): string {
            $offset++; // 0x02 INTEGER tag
            $len = ord($der[$offset]);
            $offset++;
            $bytes = substr($der, $offset, $len);
            $offset += $len;
            $bytes = ltrim($bytes, "\x00");
            return str_pad($bytes, $partLen, "\x00", STR_PAD_LEFT);
        };
        return $readInt() . $readInt();
    }

    // ------------------------------------------------------------------
    // VAPID JWT (RFC 8292)
    // ------------------------------------------------------------------
    private static function buildVapidAuthHeader(string $endpoint): ?string
    {
        $privKey = self::loadVapidPrivateKey();
        $vapidPublic = Settings::get('vapid_public', '');
        if ($privKey === null || $vapidPublic === '') {
            return null;
        }

        $origin = self::originOf($endpoint);
        $header = self::b64urlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_THROW_ON_ERROR));
        $payload = self::b64urlEncode(json_encode([
            'aud' => $origin,
            'exp' => time() + 12 * 3600,
            'sub' => Config::get('vapid.subject', 'mailto:support@birlikde.biz'),
        ], JSON_THROW_ON_ERROR));

        $signInput = $header . '.' . $payload;
        $der = '';
        if (!openssl_sign($signInput, $der, $privKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }
        $jwt = $signInput . '.' . self::b64urlEncode(self::derToRawSignature($der));

        return "vapid t={$jwt}, k={$vapidPublic}";
    }

    private static function originOf(string $url): string
    {
        $parts = parse_url($url);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    // ------------------------------------------------------------------
    // Payload şifrələnməsi (RFC 8291 aes128gcm)
    // ------------------------------------------------------------------
    private static function encryptPayload(string $p256dhRaw, string $authSecret, string $plaintext): ?string
    {
        $uaPub = self::publicKeyFromRawPoint($p256dhRaw);
        if ($uaPub === null) {
            return null;
        }

        $asPriv = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($asPriv === false) {
            return null;
        }
        $asDetails = openssl_pkey_get_details($asPriv);
        $asPublicRaw = self::rawPointFromCoords($asDetails['ec']['x'], $asDetails['ec']['y']);

        $ecdhSecret = openssl_pkey_derive($uaPub, $asPriv, 32);
        if ($ecdhSecret === false) {
            return null;
        }

        $prkKey = self::hkdfExtract($authSecret, $ecdhSecret);
        $keyInfo = "WebPush: info\x00" . $p256dhRaw . $asPublicRaw;
        $ikm = self::hkdfExpand($prkKey, $keyInfo, 32);

        $salt = random_bytes(16);
        $prk = self::hkdfExtract($salt, $ikm);
        $cek = self::hkdfExpand($prk, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = self::hkdfExpand($prk, "Content-Encoding: nonce\x00", 12);

        $padded = $plaintext . "\x02";
        $tag = '';
        $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false) {
            return null;
        }
        $ciphertext .= $tag;

        $rs = 4096;
        $header = $salt . pack('N', $rs) . chr(strlen($asPublicRaw)) . $asPublicRaw;

        return $header . $ciphertext;
    }

    // ------------------------------------------------------------------
    // Göndərmə
    // ------------------------------------------------------------------

    /**
     * @param array{endpoint:string, p256dh:string, auth_key:string} $subscription
     * @return array{ok:bool, status?:int, expired?:bool}
     */
    public static function send(array $subscription, array $payload, string $urgency = 'normal', int $ttl = 86400): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false];
        }

        $body = self::encryptPayload(
            self::b64urlDecode($subscription['p256dh']),
            self::b64urlDecode($subscription['auth_key']),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
        if ($body === null) {
            return ['ok' => false];
        }

        $authHeader = self::buildVapidAuthHeader($subscription['endpoint']);
        if ($authHeader === null) {
            return ['ok' => false];
        }

        $ch = curl_init($subscription['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: ' . $ttl,
                'Urgency: ' . $urgency,
                'Authorization: ' . $authHeader,
            ],
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 404 || $status === 410) {
            return ['ok' => false, 'status' => $status, 'expired' => true];
        }

        return ['ok' => $status >= 200 && $status < 300, 'status' => $status];
    }

    /**
     * Bir neçə istifadəçiyə push göndərir (50-lik partiyalarla, sinxron, 0.05s fasilə — 11.4).
     * @param int[] $userIds
     */
    public static function sendToUsers(array $userIds, array $payload, string $urgency = 'normal'): void
    {
        if ($userIds === [] || !self::isConfigured()) {
            return;
        }
        $chunks = array_chunk($userIds, 50);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = \App\Core\DB::conn()->prepare(
                "SELECT id, user_id, endpoint, p256dh, auth_key FROM push_subscriptions WHERE user_id IN ({$placeholders})"
            );
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll() as $sub) {
                $result = self::send($sub, $payload, $urgency);
                if (($result['expired'] ?? false) === true) {
                    $del = \App\Core\DB::conn()->prepare('DELETE FROM push_subscriptions WHERE id = ?');
                    $del->execute([$sub['id']]);
                }
                usleep(50000);
            }
        }
    }

    /**
     * sendToUsers-in eyni davranışı, lakin hər alıcı üçün mətn onun ÖZ dilində (`users.lang`)
     * qurulur — cron/kütləvi bildirişlərdə (məs. billing_reminder) fərqli dilli istifadəçilər
     * üçün lazımdır. $payloadBuilder(array $user): array formatındadır.
     * @param int[] $userIds
     */
    public static function sendToUsersLocalized(array $userIds, callable $payloadBuilder, string $urgency = 'normal'): void
    {
        if ($userIds === [] || !self::isConfigured()) {
            return;
        }
        $previousLang = Lang::current();

        $chunks = array_chunk($userIds, 50);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = \App\Core\DB::conn()->prepare(
                "SELECT ps.id, ps.user_id, ps.endpoint, ps.p256dh, ps.auth_key, u.lang
                 FROM push_subscriptions ps JOIN users u ON u.id = ps.user_id
                 WHERE ps.user_id IN ({$placeholders})"
            );
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll() as $sub) {
                Lang::use($sub['lang']);
                $payload = $payloadBuilder($sub);
                $result = self::send($sub, $payload, $urgency);
                if (($result['expired'] ?? false) === true) {
                    $del = \App\Core\DB::conn()->prepare('DELETE FROM push_subscriptions WHERE id = ?');
                    $del->execute([$sub['id']]);
                }
                usleep(50000);
            }
        }

        Lang::use($previousLang);
    }
}
