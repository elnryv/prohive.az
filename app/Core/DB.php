<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Config::get('db.host');
        $port = Config::get('db.port');
        $name = Config::get('db.name');
        $charset = Config::get('db.charset', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            self::$pdo = new PDO($dsn, Config::get('db.user'), Config::get('db.pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+04:00'",
            ]);
        } catch (PDOException $e) {
            error_log('[DB] connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('Xidmət müvəqqəti əlçatan deyil.');
        }

        return self::$pdo;
    }

    public static function beginTransaction(): void
    {
        self::conn()->beginTransaction();
    }

    public static function commit(): void
    {
        self::conn()->commit();
    }

    public static function rollBack(): void
    {
        if (self::conn()->inTransaction()) {
            self::conn()->rollBack();
        }
    }
}
