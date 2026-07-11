<?php

declare(strict_types=1);

/**
 * CLI: php database/migrate.php
 * database/migrations/*.sql fayllarını əlifba (fayl adı prefiksi) sırası ilə tətbiq edir.
 * Tətbiq olunan fayllar schema_migrations cədvəlində izlənir — təkrar işə salmaq təhlükəsizdir.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(191) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "SKIP  {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');
        $stmt->execute(['filename' => $name]);
        echo "OK    {$name}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "FAIL  {$name}: {$e->getMessage()}\n");
        exit(1);
    }
}

echo "Migrations tamamlandı.\n";
