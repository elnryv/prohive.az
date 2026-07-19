<?php
declare(strict_types=1);

// Hissə 10.16 — DB dump + uploads arxivi. Şifrə heç vaxt komanda sətrində
// göndərilmir (proc_open-un env parametri vasitəsilə MYSQL_PWD kimi ötürülür),
// ona görə `ps aux`-da görünmür.

class BackupException extends RuntimeException
{
}

function run_process(array $cmd, array $env, ?string $stdoutFile = null, ?string $stdinFile = null): void
{
    $descriptors = [
        0 => $stdinFile !== null ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
        1 => $stdoutFile !== null ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes, null, $env);
    if (!is_resource($process)) {
        throw new BackupException('Proses başladıla bilmədi: ' . implode(' ', $cmd));
    }

    if ($stdinFile === null) {
        fclose($pipes[0]);
    }
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    if ($stdoutFile === null) {
        fclose($pipes[1]);
    }

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new BackupException("Proses uğursuz oldu ({$cmd[0]}, exit code {$exitCode}): {$stderr}");
    }
}

function create_backup(array $dbConfig, string $backupDir, string $uploadsDir): string
{
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $timestamp = date('Y-m-d_His');
    $sqlPath = "{$backupDir}/db_{$timestamp}.sql";
    $archiveName = "backup_{$timestamp}.tar.gz";
    $archivePath = "{$backupDir}/{$archiveName}";

    $dumpCmd = [
        'mysqldump', '--default-character-set=utf8mb4', '--host=' . $dbConfig['host'], '--port=' . $dbConfig['port'],
        '--user=' . $dbConfig['user'], '--single-transaction', $dbConfig['name'],
    ];
    run_process($dumpCmd, ['MYSQL_PWD' => $dbConfig['pass']], $sqlPath);

    $tarCmd = ['tar', '-czf', $archivePath, '-C', $backupDir, basename($sqlPath), '-C', dirname($uploadsDir), basename($uploadsDir)];
    run_process($tarCmd, []);

    @unlink($sqlPath);

    return $archiveName;
}

function restore_backup(string $archivePath, array $dbConfig, string $storageRoot): void
{
    $tmpDir = sys_get_temp_dir() . '/ybb_restore_' . bin2hex(random_bytes(8));
    mkdir($tmpDir, 0755, true);

    try {
        run_process(['tar', '-xzf', $archivePath, '-C', $tmpDir], []);

        $sqlFiles = glob("{$tmpDir}/db_*.sql");
        if ($sqlFiles === [] || $sqlFiles === false) {
            throw new BackupException('Arxivdə DB dump tapılmadı.');
        }

        $restoreCmd = ['mysql', '--default-character-set=utf8mb4', '--host=' . $dbConfig['host'], '--port=' . $dbConfig['port'], '--user=' . $dbConfig['user'], $dbConfig['name']];
        run_process($restoreCmd, ['MYSQL_PWD' => $dbConfig['pass']], null, $sqlFiles[0]);

        $extractedUploads = "{$tmpDir}/uploads";
        if (is_dir($extractedUploads)) {
            run_process(['rsync', '-a', '--delete', $extractedUploads . '/', $storageRoot . '/uploads/'], []);
        }
    } finally {
        run_process(['rm', '-rf', $tmpDir], []);
    }
}
