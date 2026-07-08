<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const SESSION_KEY = 'user_id';

    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT id, password_hash, status FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        return Database::getInstance()->fetch(
            'SELECT id, name, email, status, is_super, created_at FROM users WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }
}
