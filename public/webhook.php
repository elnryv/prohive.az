<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Telegram\FlowEngine;
use App\Telegram\TelegramApi;
use App\Telegram\UpdateParser;

// Telegram üçün bu endpoint həmişə 200 OK qaytarmalıdır ki, təkrar-təkrar spam olunmasın.
register_shutdown_function(static function (): void {
    if (http_response_code() !== 200) {
        http_response_code(200);
        echo 'OK';
    }
});

http_response_code(200);

try {
    $secret = $_GET['s'] ?? '';
    if (!is_string($secret) || $secret === '') {
        echo 'OK';
        exit;
    }

    $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';

    $db = Database::getInstance();
    $bot = $db->fetch(
        "SELECT * FROM bots WHERE webhook_secret = :secret AND status = 'active' LIMIT 1",
        ['secret' => $secret]
    );

    if ($bot === null) {
        echo 'OK';
        exit;
    }

    if (!hash_equals((string) $bot['webhook_secret'], (string) $headerSecret)) {
        echo 'OK';
        exit;
    }

    $raw = file_get_contents('php://input');
    $decoded = $raw === false || $raw === '' ? null : json_decode($raw, true);

    if (!is_array($decoded)) {
        echo 'OK';
        exit;
    }

    $update = UpdateParser::parse($decoded);
    if ($update === null) {
        echo 'OK';
        exit;
    }

    $tg = new TelegramApi((string) $bot['telegram_token']);
    $engine = new FlowEngine($db, $bot, $tg);
    $engine->handle($update);

    echo 'OK';
} catch (\Throwable $e) {
    $line = sprintf("[%s] webhook.php exception: %s @ %s:%d\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine());
    @file_put_contents(__DIR__ . '/../storage/logs/webhook_error.log', $line, FILE_APPEND);
    http_response_code(200);
    echo 'OK';
}
