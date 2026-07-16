<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Müştəri/sürücü sessiya idarəsi. Q-Y1: telefon + şifrə, OTP yoxdur.
 * 5 cəhd/15 dəqiqə kilid, 30 gün "yadda saxla" (HMAC-imzalı cookie, stateless).
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private const SESSION_KEY = 'user_id';

    private static ?array $user = null;
    private static bool $resolved = false;

    public static function boot(): void
    {
        if (self::$resolved) {
            return;
        }
        self::$resolved = true;

        if (!empty($_SESSION[self::SESSION_KEY])) {
            self::$user = self::fetchById((int) $_SESSION[self::SESSION_KEY]);
            if (self::$user === null || (int) self::$user['is_blocked'] === 1) {
                self::logout();
            }
            return;
        }

        self::tryRememberLogin();
    }

    private static function fetchById(int $id): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function id(): ?int
    {
        return self::$user['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function isCustomer(): bool
    {
        return self::role() === 'customer';
    }

    public static function isDriver(): bool
    {
        return self::role() === 'driver';
    }

    public static function isApprovedDriver(): bool
    {
        return self::isDriver() && (self::$user['driver_status'] ?? null) === 'approved';
    }

    /**
     * @return array{ok:bool, error?:string}
     */
    public static function login(string $phoneRaw, string $password, bool $remember = false): array
    {
        $phone = Phone::normalize($phoneRaw);
        if ($phone === null) {
            return ['ok' => false, 'error' => 'auth.invalid_phone'];
        }

        $stmt = DB::conn()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();

        if ($row === false) {
            return ['ok' => false, 'error' => 'auth.invalid_credentials'];
        }

        if ($row['locked_until'] !== null && strtotime((string) $row['locked_until']) > time()) {
            return ['ok' => false, 'error' => 'auth.account_locked'];
        }

        if ((int) $row['is_blocked'] === 1) {
            return ['ok' => false, 'error' => 'auth.account_blocked'];
        }

        if (!password_verify($password, $row['password_hash'])) {
            self::registerFailedAttempt((int) $row['id'], (int) $row['failed_login_attempts']);
            return ['ok' => false, 'error' => 'auth.invalid_credentials'];
        }

        $upd = DB::conn()->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?'
        );
        $upd->execute([$row['id']]);

        self::establishSession((int) $row['id']);

        if ($remember) {
            self::issueRememberCookie((int) $row['id']);
        }

        return ['ok' => true];
    }

    private static function registerFailedAttempt(int $userId, int $currentAttempts): void
    {
        $attempts = $currentAttempts + 1;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $stmt = DB::conn()->prepare(
                'UPDATE users SET failed_login_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?'
            );
            $stmt->execute([$attempts, self::LOCK_MINUTES, $userId]);
        } else {
            $stmt = DB::conn()->prepare('UPDATE users SET failed_login_attempts = ? WHERE id = ?');
            $stmt->execute([$attempts, $userId]);
        }
    }

    public static function establishSession(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;
        self::$user = self::fetchById($userId);
        self::$resolved = true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$user = null;
        $cookieName = Config::get('session.remember_cookie', 'yuk_remember');
        setcookie($cookieName, '', ['expires' => time() - 3600, 'path' => '/']);
    }

    private static function issueRememberCookie(int $userId): void
    {
        $days = (int) Config::get('session.remember_days', 30);
        $expires = time() + 86400 * $days;
        $payload = $userId . '|' . $expires;
        $sig = hash_hmac('sha256', $payload, (string) Config::get('app_key'));
        $value = base64_encode($payload) . '.' . $sig;
        setcookie(Config::get('session.remember_cookie', 'yuk_remember'), $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => self::isHttps(),
        ]);
    }

    private static function tryRememberLogin(): void
    {
        $cookieName = Config::get('session.remember_cookie', 'yuk_remember');
        $cookie = $_COOKIE[$cookieName] ?? null;
        if (!is_string($cookie) || !str_contains($cookie, '.')) {
            return;
        }
        [$encodedPayload, $sig] = explode('.', $cookie, 2);
        $payload = base64_decode($encodedPayload, true);
        if ($payload === false) {
            return;
        }
        $expected = hash_hmac('sha256', $payload, (string) Config::get('app_key'));
        if (!hash_equals($expected, $sig)) {
            return;
        }
        [$userId, $expires] = array_pad(explode('|', $payload), 2, null);
        if ($userId === null || $expires === null || (int) $expires < time()) {
            return;
        }
        $user = self::fetchById((int) $userId);
        if ($user === null || (int) $user['is_blocked'] === 1) {
            return;
        }
        self::establishSession((int) $userId);
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function requireLogin(string $redirectTo = '/giris'): void
    {
        if (!self::check()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    public static function requireRole(string $role, string $redirectTo = '/'): void
    {
        self::requireLogin($redirectTo);
        if (self::role() !== $role) {
            http_response_code(403);
            echo 'Bu bölmə yalnız ' . ($role === 'driver' ? 'sürücülər' : 'müştərilər') . ' üçündür.';
            exit;
        }
    }

    /** users cədvəlinin aktivlik qaydası (bölmə 4.2 şərhi) — yalnız FAZA 5-də tam istifadə. */
    public static function isActiveDriver(array $user): bool
    {
        if ($user['role'] !== 'driver' || $user['driver_status'] !== 'approved' || (int) $user['is_blocked'] === 1) {
            return false;
        }
        $paymentsEnabled = Settings::get('payments_enabled', '1') === '1';
        if (!$paymentsEnabled) {
            return true;
        }
        if ($user['billing_status'] === 'free') {
            return true;
        }
        if ($user['billing_status'] === 'trial' && $user['trial_until'] !== null && $user['trial_until'] >= date('Y-m-d')) {
            return true;
        }
        if ($user['billing_status'] === 'paid' && $user['paid_until'] !== null && $user['paid_until'] >= date('Y-m-d')) {
            return true;
        }
        return false;
    }
}
