<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// error_logs cədvəli (Hissə 8) — Faza 6 admin panelində göstəriləcək.
function log_error(string $source, string $message, array $context = [], string $level = 'error'): void
{
    db()->prepare('INSERT INTO error_logs (level, source, message, context) VALUES (:level, :source, :message, :context)')
        ->execute([
            'level' => $level,
            'source' => $source,
            'message' => $message,
            'context' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
}
