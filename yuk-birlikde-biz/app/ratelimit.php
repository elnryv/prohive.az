<?php
declare(strict_types=1);

require_once __DIR__ . '/response.php';

// Fixed-window counters in APCu. Fails open (allows the request) if APCu is
// unavailable, e.g. in a CLI/dev context without the extension loaded.
function rate_limit_hit(string $bucket, int $limit, int $windowSeconds): bool
{
    if (!function_exists('apcu_inc')) {
        return true;
    }

    $key = 'rl_' . $bucket;
    if (!apcu_exists($key)) {
        apcu_add($key, 0, $windowSeconds);
    }
    $count = apcu_inc($key);

    return $count !== false && $count <= $limit;
}

function rate_limit_guard(string $bucket, int $limit, int $windowSeconds): void
{
    if (!rate_limit_hit($bucket, $limit, $windowSeconds)) {
        json_error('RATE_LIMITED', 'Çox sayda cəhd. Bir az sonra yenidən yoxlayın.', 429);
    }
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
