<?php
declare(strict_types=1);

// Diqqət: bu fayl index.php tərəfindən settings.php üzərindən dolayı yolla da
// daxil edilir, ona görə header()-lər burada deyil, yalnız json_ok/json_error
// daxilində göndərilir — əks halda HTML səhifəsinin Content-Type-ı korlanır.
function json_ok(array $data = [], int $status = 200): never
{
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'");
    http_response_code($status);
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $code, string $message, int $status = 400): never
{
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'");
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    exit;
}

function request_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('METHOD_NOT_ALLOWED', 'Bu metod dəstəklənmir.', 405);
    }
}
