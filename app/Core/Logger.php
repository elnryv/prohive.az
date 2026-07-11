<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $path = self::logPath();
        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : '',
            PHP_EOL
        );

        error_log($line, 3, $path);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    private static function logPath(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . '/app-' . date('Y-m-d') . '.log';
    }
}
