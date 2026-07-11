<?php

declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        Logger::error($message, ['file' => $file, 'line' => $line, 'severity' => $severity]);

        if (Env::getBool('APP_DEBUG', false)) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }

        return true;
    }

    public static function handleException(\Throwable $exception): void
    {
        Logger::error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        self::respond($exception);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        Logger::error($error['message'], ['file' => $error['file'], 'line' => $error['line']]);

        if (!headers_sent()) {
            self::respond(null);
        }
    }

    private static function respond(?\Throwable $exception): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        $debug = Env::getBool('APP_DEBUG', false);
        $payload = ['error' => 'Daxili server xətası'];

        if ($debug && $exception !== null) {
            $payload['debug'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
