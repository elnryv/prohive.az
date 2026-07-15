<?php

declare(strict_types=1);

/**
 * CLI: php database/create_admin.php <telefon> <ad> <soyad>
 * Web-dən admin qeydiyyat endpoint-i QƏSDƏN yoxdur (bax CLAUDE.md bölmə 8.8) —
 * admin hesabları yalnız server konsolundan, bu skriptlə yaradılır. Parol
 * terminalda gizli daxil edilir, heç vaxt arqument/faylda saxlanmır.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Admin;

$telefon = $argv[1] ?? null;
$ad = $argv[2] ?? null;
$soyad = $argv[3] ?? null;

if ($telefon === null || $ad === null || $soyad === null) {
    fwrite(STDERR, "İstifadə: php database/create_admin.php <telefon> <ad> <soyad>\n");
    exit(1);
}

$telefon = (string) preg_replace('/\D/', '', $telefon);
if ($telefon === '') {
    fwrite(STDERR, "Telefon nömrəsi düzgün deyil.\n");
    exit(1);
}

fwrite(STDOUT, "Parol (ən azı 8 simvol): ");
system('stty -echo');
$parol = trim((string) fgets(STDIN));
system('stty echo');
fwrite(STDOUT, "\n");

if (strlen($parol) < 8) {
    fwrite(STDERR, "Admin parolu ən azı 8 simvol olmalıdır.\n");
    exit(1);
}

$adminModel = new Admin();

if ($adminModel->findByTelefon($telefon) !== null) {
    fwrite(STDERR, "Bu telefon nömrəsi ilə admin artıq mövcuddur.\n");
    exit(1);
}

$id = $adminModel->create($ad, $soyad, $telefon, password_hash($parol, PASSWORD_DEFAULT));

echo "Admin yaradıldı (id={$id}).\n";
