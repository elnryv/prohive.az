<?php
declare(strict_types=1);

namespace App\Telegram;

final class TelegramApi
{
    private string $token;
    private string $apiBase;
    private bool $testMode;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->apiBase = rtrim((string) config()['telegram']['api'], '/');
        $this->testMode = config()['app']['env'] === 'test';
    }

    public function sendMessage(int|string $chatId, string $text, ?array $inlineKeyboard = null, string $parseMode = 'HTML'): array|false
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard], JSON_UNESCAPED_UNICODE);
        }

        return $this->call('sendMessage', $payload);
    }

    public function sendPhoto(int|string $chatId, string $photoUrl, ?string $caption = null): array|false
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->call('sendPhoto', $payload);
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, ?array $inlineKeyboard = null): array|false
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($inlineKeyboard !== null) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard], JSON_UNESCAPED_UNICODE);
        }

        return $this->call('editMessageText', $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array|false
    {
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text !== null) {
            $payload['text'] = $text;
        }

        return $this->call('answerCallbackQuery', $payload);
    }

    public function getMe(): array|false
    {
        return $this->call('getMe', []);
    }

    public function setWebhook(string $url, string $secretToken): array|false
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
        ]);
    }

    public function deleteWebhook(): array|false
    {
        return $this->call('deleteWebhook', []);
    }

    /**
     * Sadə API sorğusu (GET/POST üçün cURL). Test rejimində real şəbəkə çağırışı etmir,
     * payload-u storage/logs/telegram_test.log-a yazır və saxta uğurlu cavab qaytarır.
     */
    private function call(string $method, array $payload): array|false
    {
        if ($this->testMode) {
            $this->logTest($method, $payload);
            return ['ok' => true, 'result' => ['test_mode' => true, 'method' => $method, 'payload' => $payload]];
        }

        $url = sprintf('%s/bot%s/%s', $this->apiBase, $this->token, $method);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->logError($method, $payload, $error);
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            $this->logError($method, $payload, (string) $response);
            return false;
        }

        return $decoded;
    }

    private function logTest(string $method, array $payload): void
    {
        $line = sprintf(
            "[%s] TEST-MODE %s %s\n",
            date('Y-m-d H:i:s'),
            $method,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/telegram_test.log', $line, FILE_APPEND);
    }

    private function logError(string $method, array $payload, string $error): void
    {
        $line = sprintf(
            "[%s] ERROR %s %s | %s\n",
            date('Y-m-d H:i:s'),
            $method,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $error
        );
        @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/telegram_error.log', $line, FILE_APPEND);
    }
}
