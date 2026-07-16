<?php
declare(strict_types=1);

/**
 * POST /api/track/wa — yalnız sayğac, CSRF-dən istisna (12.1),
 * amma Origin yoxlanışı + rate limit tələb olunur.
 */
final class Track
{
    public function wa(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
            return;
        }

        if (!$this->originIsTrusted()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'origin']);
            return;
        }

        $ip = RateLimit::clientIp();
        if (!RateLimit::attempt('track_wa', $ip, 30, 60)) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'rate_limited']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        $houseId = is_array($body) && isset($body['house_id']) ? (int) $body['house_id'] : 0;

        if ($houseId <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'invalid_house_id']);
            return;
        }

        $exists = DB::one('SELECT id FROM houses WHERE id = :id', [':id' => $houseId]);
        if ($exists === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            return;
        }

        HouseRepository::incrementWaClick($houseId);

        echo json_encode(['ok' => true]);
    }

    private function originIsTrusted(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if ($origin === '') {
            // Bəzi brauzerlər keepalive fetch-də Origin göndərməyə bilər; Host özü ilə davam.
            return true;
        }
        $host = parse_url($origin, PHP_URL_HOST);
        $expectedHost = parse_url(SITE_BASE_URL, PHP_URL_HOST);
        return $host === $expectedHost || $host === ($_SERVER['HTTP_HOST'] ?? null);
    }
}
