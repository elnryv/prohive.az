<?php
declare(strict_types=1);

/** Admin əməliyyatları həmişə admin_logs-a yazılır (bölmə 4.10, 9.3 — səbəb tələb olunmur). */
final class AdminLog
{
    public static function write(string $action, ?int $targetId = null, ?string $details = null): void
    {
        $adminId = AdminAuth::id();
        if ($adminId === null) {
            return;
        }
        DB::query(
            'INSERT INTO admin_logs (admin_id, action, target_id, details, ip, created_at)
             VALUES (:admin_id, :action, :target_id, :details, :ip, NOW())',
            [
                ':admin_id' => $adminId,
                ':action' => $action,
                ':target_id' => $targetId,
                ':details' => $details,
                ':ip' => RateLimit::clientIp(),
            ]
        );
    }
}
