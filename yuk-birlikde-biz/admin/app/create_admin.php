<?php
declare(strict_types=1);

// Bir dəfəlik CLI skripti: ilk admin hesabını yaradır.
// İstifadə: php admin/app/create_admin.php <username> <email> <password>

require_once __DIR__ . '/../../app/db.php';

if (PHP_SAPI !== 'cli') {
    exit('Yalnız CLI-dən işə salına bilər.');
}

[$script, $username, $email, $password] = array_pad($argv, 4, null);

if ($username === null || $email === null || $password === null) {
    echo "İstifadə: php admin/app/create_admin.php <username> <email> <password>\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "Şifrə ən azı 8 simvol olmalıdır.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_ARGON2ID);

$stmt = db()->prepare(
    'INSERT INTO admin_users (username, email, password_hash) VALUES (:username, :email, :hash)
     ON DUPLICATE KEY UPDATE password_hash = :hash2'
);
$stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash, 'hash2' => $hash]);

echo "Admin hesabı hazırdır: {$username} / {$email}\n";
