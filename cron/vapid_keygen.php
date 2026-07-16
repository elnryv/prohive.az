<?php

declare(strict_types=1);

/**
 * VAPID açar cütü generasiyası (bölmə 11.4). Bir dəfə əl ilə icra olunur:
 *   php cron/vapid_keygen.php
 * Nəticə birbaşa `settings` cədvəlinə (vapid_public/vapid_private) yazılır.
 * Composer/kitabxana tələb olunmur — yalnız ext-openssl (PHP 8.1+).
 */

require __DIR__ . '/bootstrap.php';

use App\Core\DB;
use App\Core\Settings;
use App\Core\WebPush;

$existing = Settings::get('vapid_public', '');
if ($existing !== '' && !in_array('--force', $argv, true)) {
    fwrite(STDERR, "VAPID açarları artıq mövcuddur. Yenidən yaratmaq üçün --force ver (DİQQƏT: bütün\n");
    fwrite(STDERR, "mövcud push abunəlikləri işləməz olacaq, istifadəçilər yenidən abunə olmalıdır).\n");
    exit(1);
}

$keys = WebPush::generateVapidKeys();

Settings::set('vapid_public', $keys['public']);
Settings::set('vapid_private', $keys['private']);

if (in_array('--force', $argv, true)) {
    DB::conn()->exec('DELETE FROM push_subscriptions');
    echo "Köhnə push abunəlikləri təmizləndi (açar dəyişdiyi üçün etibarsızdırlar).\n";
}

echo "VAPID açarları uğurla generasiya olundu və settings cədvəlinə yazıldı.\n";
echo "Public key (JS applicationServerKey üçün istinad, settings.vapid_public-dən avtomatik oxunur):\n";
echo $keys['public'] . "\n";
