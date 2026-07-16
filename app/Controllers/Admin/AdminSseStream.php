<?php
declare(strict_types=1);

/** GET /sse — admin dashboard-ın canlı kanalı (bölmə 11.4). */
final class AdminSseStream
{
    public function handle(): void
    {
        if (!AdminAuth::check()) {
            http_response_code(401);
            return;
        }
        Sse::stream('admin');
    }
}
