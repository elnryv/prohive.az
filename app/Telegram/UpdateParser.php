<?php
declare(strict_types=1);

namespace App\Telegram;

final class UpdateParser
{
    /**
     * Telegram-dan gələn raw update massivini normallaşdırır.
     */
    public static function parse(array $update): ?array
    {
        if (isset($update['callback_query'])) {
            $cq = $update['callback_query'];
            $message = $cq['message'] ?? [];
            $from = $cq['from'] ?? [];

            return [
                'type' => 'callback',
                'chat_id' => (int) ($message['chat']['id'] ?? $from['id'] ?? 0),
                'text' => null,
                'callback_data' => $cq['data'] ?? null,
                'callback_id' => $cq['id'] ?? null,
                'message_id' => $message['message_id'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
            ];
        }

        if (isset($update['message'])) {
            $message = $update['message'];
            $from = $message['from'] ?? [];

            return [
                'type' => 'message',
                'chat_id' => (int) ($message['chat']['id'] ?? 0),
                'text' => $message['text'] ?? null,
                'callback_data' => null,
                'callback_id' => null,
                'message_id' => $message['message_id'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
            ];
        }

        return null;
    }
}
