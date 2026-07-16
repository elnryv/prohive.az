<?php

declare(strict_types=1);

namespace App\Core;

final class AdminAuth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private const SESSION_KEY = 'admin_id';

    private static ?array $admin = null;
    private static bool $resolved = false;

    public static function boot(): void
    {
        if (self::$resolved) {
            return;
        }
        self::$resolved = true;
        if (!empty($_SESSION[self::SESSION_KEY])) {
            self::$admin = self::fetchById((int) $_SESSION[self::SESSION_KEY]);
        }
    }

    private static function fetchById(int $id): ?array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function admin(): ?array
    {
        return self::$admin;
    }

    public static function check(): bool
    {
        return self::$admin !== null;
    }

    public static function id(): ?int
    {
        return self::$admin['id'] ?? null;
    }

    public static function login(string $username, string $password): array
    {
        $stmt = DB::conn()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row === false) {
            return ['ok' => false, 'error' => 'Yanlış istifadəçi adı və ya şifrə'];
        }

        if ($row['locked_until'] !== null && strtotime((string) $row['locked_until']) > time()) {
            return ['ok' => false, 'error' => 'Çox sayda cəhd — 15 dəqiqə sonra yenidən cəhd et'];
        }

        if (!password_verify($password, $row['password_hash'])) {
            $attempts = (int) $row['failed_attempts'] + 1;
            if ($attempts >= self::MAX_ATTEMPTS) {
                $upd = DB::conn()->prepare(
                    'UPDATE admins SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?'
                );
                $upd->execute([$attempts, self::LOCK_MINUTES, $row['id']]);
            } else {
                $upd = DB::conn()->prepare('UPDATE admins SET failed_attempts = ? WHERE id = ?');
                $upd->execute([$attempts, $row['id']]);
            }
            return ['ok' => false, 'error' => 'Yanlış istifadəçi adı və ya şifrə'];
        }

        $upd = DB::conn()->prepare(
            'UPDATE admins SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?'
        );
        $upd->execute([$row['id']]);

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $row['id'];
        self::$admin = $row;
        self::$resolved = true;

        return ['ok' => true];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$admin = null;
    }

    public static function requireLogin(string $redirectTo = '/giris'): void
    {
        if (!self::check()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    public static function log(string $action, ?string $targetType = null, ?int $targetId = null, array $details = []): void
    {
        $stmt = DB::conn()->prepare(
            'INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            self::id(),
            $action,
            $targetType,
            $targetId,
            $details !== [] ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
