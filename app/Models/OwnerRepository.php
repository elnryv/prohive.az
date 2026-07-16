<?php
declare(strict_types=1);

final class OwnerRepository
{
    public static function findByPhone(string $normalizedPhone): ?array
    {
        return DB::one('SELECT * FROM owners WHERE phone = :p', [':p' => $normalizedPhone]);
    }

    public static function findById(int $id): ?array
    {
        return DB::one('SELECT * FROM owners WHERE id = :id', [':id' => $id]);
    }

    public static function create(string $normalizedPhone, string $passwordHash, string $fullName, int $trialDays): int
    {
        DB::query(
            "INSERT INTO owners (phone, password_hash, full_name, billing_status, trial_until, created_at)
             VALUES (:phone, :hash, :name, 'trial', CURDATE() + INTERVAL :days DAY, NOW())",
            [
                ':phone' => $normalizedPhone,
                ':hash' => $passwordHash,
                ':name' => $fullName,
                ':days' => $trialDays,
            ]
        );
        return (int) DB::lastInsertId();
    }

    public static function touchLastLogin(int $ownerId): void
    {
        DB::query('UPDATE owners SET last_login_at = NOW() WHERE id = :id', [':id' => $ownerId]);
    }

    /** Görünürlük qaydasına (4.3) uyğun ev sahibi aktivdirmi (evləri saytda görünür) */
    public static function isVisible(array $owner): bool
    {
        return match ($owner['billing_status']) {
            'trial' => $owner['trial_until'] !== null && $owner['trial_until'] >= date('Y-m-d'),
            'paid' => $owner['paid_until'] !== null && $owner['paid_until'] >= date('Y-m-d'),
            'free' => true,
            default => false,
        };
    }

    public static function effectivePrice(array $owner): float
    {
        if ($owner['custom_price'] !== null) {
            return (float) $owner['custom_price'];
        }
        return SettingsRepository::getFloat('default_monthly_price', 25.0);
    }
}
