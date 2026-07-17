<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Settings;

final class PushController
{
    public function vapidPublicKey(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['key' => Settings::get('vapid_public', '')]);
    }

    public function subscribe(): void
    {
        Auth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $endpoint = (string) ($body['endpoint'] ?? '');
        $p256dh = (string) ($body['keys']['p256dh'] ?? '');
        $authKey = (string) ($body['keys']['auth'] ?? '');

        if ($endpoint === '' || $p256dh === '' || $authKey === '') {
            http_response_code(422);
            return;
        }

        $stmt = DB::conn()->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth_key = VALUES(auth_key)'
        );
        $stmt->execute([Auth::id(), $endpoint, $p256dh, $authKey]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function unsubscribe(): void
    {
        Auth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $endpoint = (string) ($body['endpoint'] ?? '');
        if ($endpoint !== '') {
            $stmt = DB::conn()->prepare('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?');
            $stmt->execute([Auth::id(), $endpoint]);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }
}
