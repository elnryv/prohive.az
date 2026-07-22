<?php
declare(strict_types=1);

// Yalnız lokal inkişaf üçün: `php -S 0.0.0.0:8000 router-dev.php` (public/ daxilində).
// Production-da nginx.conf.example istifadə olunur.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// {id}/{slug} parametrli marşrutlar — nginx.conf.example-dəki rewrite qaydalarının lokal ekvivalenti.
$paramRoutes = [
    '#^/api/v1/pages/([a-zA-Z0-9-]+)$#' => ['api/v1/pages.php', 'slug'],
    '#^/api/v1/orders/(\d+)/images$#' => ['api/v1/orders/images.php', 'id'],
    '#^/api/v1/orders/(\d+)/close$#' => ['api/v1/orders/close.php', 'id'],
    '#^/api/v1/orders/(\d+)/cancel-selection$#' => ['api/v1/orders/cancel-selection.php', 'id'],
    '#^/api/v1/orders/(\d+)/republish$#' => ['api/v1/orders/republish.php', 'id'],
    '#^/api/v1/orders/(\d+)$#' => ['api/v1/orders/detail.php', 'id'],
    '#^/api/v1/feed/(\d+)$#' => ['api/v1/feed/detail.php', 'id'],
    '#^/api/v1/offers/(\d+)/withdraw$#' => ['api/v1/offers/withdraw.php', 'id'],
    '#^/api/v1/offers/(\d+)/select$#' => ['api/v1/offers/select.php', 'id'],
    '#^/api/v1/offers/(\d+)/decline$#' => ['api/v1/offers/decline.php', 'id'],
    '#^/api/v1/offers/(\d+)$#' => ['api/v1/offers/update.php', 'id'],
    '#^/api/v1/banners/(\d+)/click$#' => ['api/v1/banners/click.php', 'id'],
    '#^/og/([a-f0-9]+)\.png$#' => ['og/generate.php', 'slug'],
    '#^/e/([a-f0-9]+)$#' => ['e/index.php', 'slug'],
];

foreach ($paramRoutes as $pattern => [$file, $param]) {
    if (preg_match($pattern, $uri, $m)) {
        $_GET[$param] = $m[1];
        require __DIR__ . '/' . $file;
        return true;
    }
}

if (str_starts_with($uri, '/uploads/')) {
    $path = __DIR__ . '/../storage/uploads/' . substr($uri, strlen('/uploads/'));
    $real = realpath($path);
    $storageRoot = realpath(__DIR__ . '/../storage/uploads');
    if ($real === false || !str_starts_with($real, $storageRoot)) {
        http_response_code(404);
        return true;
    }
    $mime = match (strtolower(pathinfo($real, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };
    header("Content-Type: {$mime}");
    readfile($real);
    return true;
}

if ($uri !== '/' && is_file(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false; // static asset — built-in server serves it directly
}

if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/sse/')) {
    $script = str_ends_with($uri, '.php') ? __DIR__ . $uri : __DIR__ . $uri . '.php';
    if (file_exists($script)) {
        require $script;
        return true;
    }
    http_response_code(404);
    return true;
}

require __DIR__ . '/index.php';
return true;
