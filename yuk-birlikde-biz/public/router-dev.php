<?php
declare(strict_types=1);

// Yalnız lokal inkişaf üçün: `php -S 0.0.0.0:8000 router-dev.php` (public/ daxilində).
// Production-da nginx.conf.example istifadə olunur.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/api/v1/pages/([a-zA-Z0-9-]+)$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/api/v1/pages.php';
    return true;
}

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false; // static asset — built-in server serves it directly
}

if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/sse/')) {
    $script = __DIR__ . $uri . '.php';
    if (file_exists($script)) {
        require $script;
        return true;
    }
    http_response_code(404);
    return true;
}

require __DIR__ . '/index.php';
return true;
