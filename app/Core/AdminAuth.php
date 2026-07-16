<?php
declare(strict_types=1);

/**
 * Admin sessiyası (bölmə 9). 30 dəq hərəkətsizlik → çıxış.
 * Login qorunması: 2 uğursuz cəhddən sonra captcha əvəzinə 30 saniyə süni
 * gecikmə, 5 uğursuz cəhd → 15 dəq IP kilidi.
 */
final class AdminAuth
{
    private const IDLE_TIMEOUT = 1800;

    public static function login(int $adminId): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_last_activity'] = time();
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_last_activity']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        if (isset($_SESSION['admin_last_activity']) && (time() - (int) $_SESSION['admin_last_activity']) > self::IDLE_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['admin_id'] : null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /giris');
            exit;
        }
    }

    /** Uğursuz cəhd limiti aşılıbmı (5/15dəq, IP-ə görə) */
    public static function isLocked(string $ip): bool
    {
        return RateLimit::tooMany('admin_login', $ip, 5, 900);
    }

    /** 2-ci uğursuz cəhddən sonra captcha əvəzinə süni gecikmə */
    public static function applyDelayIfNeeded(string $ip): void
    {
        if (RateLimit::count('admin_login', $ip, 900) >= 2) {
            sleep(30);
        }
    }

    public static function recordFailure(string $ip): void
    {
        RateLimit::hit('admin_login', $ip, 900);
    }

    public static function clearFailures(string $ip): void
    {
        RateLimit::clear('admin_login', $ip);
    }
}
