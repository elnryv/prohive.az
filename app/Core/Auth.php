<?php
declare(strict_types=1);

/**
 * Ev sahibi sessiyası (Owner/Register, Login, Dashboard və s. üçün).
 * Remember-token cədvəlsiz — imzalı cookie (HMAC), bölmə 12.1.
 */
final class Auth
{
    private const REMEMBER_DAYS = 30;
    private static ?array $userCache = null;
    private static bool $userCacheLoaded = false;

    public static function login(int $ownerId, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['owner_id'] = $ownerId;
        self::$userCache = null;
        self::$userCacheLoaded = false;

        if ($remember) {
            self::setRememberCookie($ownerId);
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['owner_id']);
        self::$userCache = null;
        self::$userCacheLoaded = false;
        session_regenerate_id(true);

        if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
            setcookie(REMEMBER_COOKIE_NAME, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        if (isset($_SESSION['owner_id'])) {
            return (int) $_SESSION['owner_id'];
        }

        $remembered = self::attemptRememberLogin();
        return $remembered;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$userCacheLoaded) {
            return self::$userCache;
        }
        self::$userCacheLoaded = true;

        $id = self::id();
        if ($id === null) {
            return self::$userCache = null;
        }

        return self::$userCache = DB::one('SELECT * FROM owners WHERE id = :id', [':id' => $id]);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /sahib/giris');
            exit;
        }
    }

    private static function setRememberCookie(int $ownerId): void
    {
        $exp = time() + self::REMEMBER_DAYS * 86400;
        $payload = base64_encode((string) json_encode(['id' => $ownerId, 'exp' => $exp]));
        $signature = hash_hmac('sha256', $payload, REMEMBER_SECRET);

        setcookie(REMEMBER_COOKIE_NAME, $payload . '.' . $signature, [
            'expires' => $exp,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function attemptRememberLogin(): ?int
    {
        $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
        if (!is_string($cookie) || !str_contains($cookie, '.')) {
            return null;
        }

        [$payload, $signature] = explode('.', $cookie, 2);
        $expected = hash_hmac('sha256', $payload, REMEMBER_SECRET);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $data = json_decode((string) base64_decode($payload, true), true);
        if (!is_array($data) || empty($data['id']) || empty($data['exp']) || $data['exp'] < time()) {
            return null;
        }

        $ownerId = (int) $data['id'];
        session_regenerate_id(true);
        $_SESSION['owner_id'] = $ownerId;

        return $ownerId;
    }
}
