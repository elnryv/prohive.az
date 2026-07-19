<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

// Hissə 6.1 — ödənişli rejimdə təklif göndərmək üçün aktiv abunə tələb olunur.
function subscription_mode_paid(): bool
{
    return settings_get('subscription_mode', 'free') === 'paid';
}

function subscription_is_active(int $driverId): bool
{
    return subscription_current($driverId) !== null;
}

function subscription_current(int $driverId): ?array
{
    $stmt = db()->prepare(
        "SELECT id, starts_at, ends_at, status, source FROM subscriptions
         WHERE driver_id = :id AND status = 'active' AND ends_at > NOW()
         ORDER BY ends_at DESC LIMIT 1"
    );
    $stmt->execute(['id' => $driverId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Ödəniş uğurlu olduqda (Payriff) və ya admin əl ilə verdikdə (Faza 6) çağırılır.
// subscription_activation ('immediate' | 'stack'): dərhal yeni müddət başlayır,
// yoxsa mövcud aktiv abunənin bitməsinin üstünə əlavə olunur (Hissə 6.2/6.3).
function subscription_grant(int $driverId, string $source, int $days, ?int $adminId = null): array
{
    $db = db();
    $mode = settings_get('subscription_activation', 'immediate');
    $current = subscription_current($driverId);

    if ($current !== null && $mode === 'stack') {
        $endsAt = date('Y-m-d H:i:s', strtotime($current['ends_at']) + $days * 86400);
        $db->prepare('UPDATE subscriptions SET ends_at = :ends_at WHERE id = :id')
            ->execute(['ends_at' => $endsAt, 'id' => $current['id']]);
        return ['id' => (int) $current['id'], 'ends_at' => $endsAt];
    }

    if ($current !== null) {
        $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = :id")
            ->execute(['id' => $current['id']]);
    }

    $startsAt = date('Y-m-d H:i:s');
    $endsAt = date('Y-m-d H:i:s', time() + $days * 86400);
    $stmt = $db->prepare(
        'INSERT INTO subscriptions (driver_id, source, starts_at, ends_at, status, granted_by_admin_id)
         VALUES (:driver_id, :source, :starts_at, :ends_at, "active", :admin_id)'
    );
    $stmt->execute([
        'driver_id' => $driverId,
        'source' => $source,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'admin_id' => $adminId,
    ]);

    return ['id' => (int) $db->lastInsertId(), 'ends_at' => $endsAt];
}
