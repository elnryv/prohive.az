<?php
declare(strict_types=1);

// Nümunə fayl — real açarlar üçün: php app/generate_vapid_keys.php
// Bu, app/vapid_keys.php yaradır (git-ə düşmür).

return [
    'public_key_raw' => 'BASE64URL_ENCODED_UNCOMPRESSED_P256_PUBLIC_KEY',
    'private_key' => "-----BEGIN EC PRIVATE KEY-----\n...\n-----END EC PRIVATE KEY-----\n",
    'subject' => 'mailto:admin@yuk.birlikde.biz',
];
