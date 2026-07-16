<?php
declare(strict_types=1);

/** GET /sse — ev sahibi panelinin canlı kanalı (bölmə 11.4). */
final class SseStream
{
    public function handle(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }
        Sse::stream('owner_' . Auth::id());
    }
}
