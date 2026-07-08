<?php
declare(strict_types=1);

namespace App\Telegram;

use App\Core\Database;
use Throwable;

final class FlowEngine
{
    private const MAX_GOTO_DEPTH = 10;

    private Database $db;
    private array $bot;
    private TelegramApi $tg;

    public function __construct(Database $db, array $bot, TelegramApi $tg)
    {
        $this->db = $db;
        $this->bot = $bot;
        $this->tg = $tg;
    }

    /**
     * Normallaşdırılmış update-i (UpdateParser::parse() çıxışı) qəbul edir və emal edir.
     */
    public function handle(array $update): void
    {
        try {
            $this->process($update);
        } catch (Throwable $e) {
            $this->logError('FlowEngine::handle exception: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function process(array $update): void
    {
        $botId = (int) $this->bot['id'];
        $chatId = (int) $update['chat_id'];

        $subscriber = $this->findOrCreateSubscriber($botId, $chatId, $update);

        $this->logMessage(
            $botId,
            (int) $subscriber['id'],
            'in',
            $update['type'] === 'callback' ? 'callback' : 'text',
            $update['type'] === 'callback' ? $update['callback_data'] : $update['text'],
            $update
        );

        $targetNode = null;

        // a) callback_data
        if (!empty($update['callback_data'])) {
            $button = $this->findButtonById((int) $update['callback_data']);
            if ($button !== null) {
                $targetNode = $this->getNode((int) $button['target_node_id']);
                $this->tg->answerCallbackQuery((string) $update['callback_id']);

                if (!empty($button['action_tag_id'])) {
                    $this->addTagToSubscriber((int) $subscriber['id'], (int) $button['action_tag_id']);
                }
            } else {
                $this->tg->answerCallbackQuery((string) $update['callback_id']);
            }
        }
        // b) /start
        elseif (($update['text'] ?? null) === '/start') {
            if (!empty($this->bot['start_node_id'])) {
                $targetNode = $this->getNode((int) $this->bot['start_node_id']);
            }
        }
        // c) waiting_for input
        elseif (!empty($subscriber['waiting_for'])) {
            $fieldKey = $subscriber['waiting_for'];
            $this->saveSubscriberField((int) $subscriber['id'], $fieldKey, (string) ($update['text'] ?? ''));

            $this->db->update(
                'bot_subscribers',
                ['waiting_for' => null],
                'id = :id',
                ['id' => $subscriber['id']]
            );
            $subscriber['waiting_for'] = null;

            $currentNode = !empty($subscriber['current_node_id'])
                ? $this->getNode((int) $subscriber['current_node_id'])
                : null;

            if ($currentNode !== null && !empty($currentNode['input_next_node'])) {
                $targetNode = $this->getNode((int) $currentNode['input_next_node']);
            }
        }
        // d) keyword match
        elseif (!empty($update['text']) && ($keyword = $this->findKeywordMatch($botId, (string) $update['text'])) !== null) {
            $targetNode = $this->getNode((int) $keyword['target_node_id']);
        }

        // e) fallback
        if ($targetNode === null) {
            $fallback = $this->db->fetch(
                "SELECT * FROM bot_nodes WHERE bot_id = :bot_id AND node_key = 'fallback' LIMIT 1",
                ['bot_id' => $botId]
            );

            if ($fallback !== null) {
                $targetNode = $fallback;
            } elseif (!empty($this->bot['start_node_id'])) {
                $targetNode = $this->getNode((int) $this->bot['start_node_id']);
            }
        }

        if ($targetNode === null) {
            return;
        }

        $finalNodeId = $this->renderNode($targetNode, $subscriber, 0);

        $this->db->update(
            'bot_subscribers',
            ['current_node_id' => $finalNodeId],
            'id = :id',
            ['id' => $subscriber['id']]
        );
    }

    /**
     * Node-u icra edir, tipinə görə mesaj göndərir. Rekursiv (goto) çağırışlar üçün
     * dərinlik sayğacı saxlayır. Son çatılan node-un id-sini qaytarır.
     */
    private function renderNode(array $node, array $subscriber, int $depth): ?int
    {
        if ($depth >= self::MAX_GOTO_DEPTH) {
            $this->logError(sprintf(
                'FlowEngine: goto recursion limit reached (bot_id=%d, node_id=%d)',
                $this->bot['id'],
                $node['id']
            ));
            return (int) $node['id'];
        }

        $chatId = (int) $subscriber['telegram_chat_id'];

        switch ($node['node_type']) {
            case 'text':
                $text = $this->renderTemplate((string) $node['message_text'], $subscriber);
                $this->tg->sendMessage($chatId, $text);
                $this->logMessage((int) $this->bot['id'], (int) $subscriber['id'], 'out', 'text', $text, []);
                return (int) $node['id'];

            case 'buttons':
                $text = $this->renderTemplate((string) $node['message_text'], $subscriber);
                $keyboard = $this->buildKeyboard((int) $node['id']);
                $this->tg->sendMessage($chatId, $text, $keyboard);
                $this->logMessage((int) $this->bot['id'], (int) $subscriber['id'], 'out', 'buttons', $text, []);
                return (int) $node['id'];

            case 'input':
                $text = $this->renderTemplate((string) $node['message_text'], $subscriber);
                $this->tg->sendMessage($chatId, $text);
                $this->db->update(
                    'bot_subscribers',
                    ['waiting_for' => $node['input_field_key']],
                    'id = :id',
                    ['id' => $subscriber['id']]
                );
                $this->logMessage((int) $this->bot['id'], (int) $subscriber['id'], 'out', 'input', $text, []);
                return (int) $node['id'];

            case 'image':
                $caption = $node['message_text'] !== null
                    ? $this->renderTemplate((string) $node['message_text'], $subscriber)
                    : null;
                $this->tg->sendPhoto($chatId, (string) $node['media_url'], $caption);
                $this->logMessage((int) $this->bot['id'], (int) $subscriber['id'], 'out', 'image', $caption, []);
                return (int) $node['id'];

            case 'delay':
                // Sadə (sinxron) versiya: gecikməni real gözləmirik, sadəcə qeyd edirik.
                $this->logMessage(
                    (int) $this->bot['id'],
                    (int) $subscriber['id'],
                    'out',
                    'delay',
                    sprintf('delay_seconds=%s', $node['delay_seconds'] ?? 0),
                    []
                );
                if (!empty($node['goto_node_id'])) {
                    $next = $this->getNode((int) $node['goto_node_id']);
                    if ($next !== null) {
                        return $this->renderNode($next, $subscriber, $depth + 1);
                    }
                }
                return (int) $node['id'];

            case 'goto':
                if (!empty($node['goto_node_id'])) {
                    $next = $this->getNode((int) $node['goto_node_id']);
                    if ($next !== null) {
                        return $this->renderNode($next, $subscriber, $depth + 1);
                    }
                }
                return (int) $node['id'];

            case 'api':
                $this->callApiNode($node, $subscriber);
                return (int) $node['id'];

            case 'final':
                if (!empty($node['message_text'])) {
                    $text = $this->renderTemplate((string) $node['message_text'], $subscriber);
                    $this->tg->sendMessage($chatId, $text);
                    $this->logMessage((int) $this->bot['id'], (int) $subscriber['id'], 'out', 'final', $text, []);
                }
                return (int) $node['id'];

            default:
                return (int) $node['id'];
        }
    }

    private function callApiNode(array $node, array $subscriber): void
    {
        $url = (string) $node['api_url'];
        if ($url === '') {
            return;
        }

        $method = strtoupper((string) ($node['api_method'] ?? 'POST'));

        try {
            $ch = curl_init();
            if ($method === 'GET') {
                curl_setopt($ch, CURLOPT_URL, $url);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'subscriber_id' => $subscriber['id'],
                    'chat_id' => $subscriber['telegram_chat_id'],
                ], JSON_UNESCAPED_UNICODE));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            curl_close($ch);

            $this->logMessage(
                (int) $this->bot['id'],
                (int) $subscriber['id'],
                'out',
                'api',
                is_string($response) ? substr($response, 0, 1000) : null,
                []
            );
        } catch (Throwable $e) {
            $this->logError('FlowEngine api node error: ' . $e->getMessage());
        }
    }

    private function buildKeyboard(int $nodeId): ?array
    {
        $buttons = $this->db->fetchAll(
            'SELECT * FROM bot_node_buttons WHERE node_id = :node_id ORDER BY sort_order ASC, id ASC',
            ['node_id' => $nodeId]
        );

        if ($buttons === []) {
            return null;
        }

        $rows = [];
        foreach ($buttons as $button) {
            if ($button['button_type'] === 'url') {
                $rows[] = [['text' => $button['button_text'], 'url' => $button['url']]];
            } else {
                $rows[] = [['text' => $button['button_text'], 'callback_data' => (string) $button['id']]];
            }
        }

        return $rows;
    }

    private function findOrCreateSubscriber(int $botId, int $chatId, array $update): array
    {
        $subscriber = $this->db->fetch(
            'SELECT * FROM bot_subscribers WHERE bot_id = :bot_id AND telegram_chat_id = :chat_id LIMIT 1',
            ['bot_id' => $botId, 'chat_id' => $chatId]
        );

        if ($subscriber !== null) {
            $this->db->update(
                'bot_subscribers',
                [
                    'last_seen_at' => date('Y-m-d H:i:s'),
                    'first_name' => $update['first_name'] ?? $subscriber['first_name'],
                    'last_name' => $update['last_name'] ?? $subscriber['last_name'],
                    'username' => $update['username'] ?? $subscriber['username'],
                ],
                'id = :id',
                ['id' => $subscriber['id']]
            );

            return array_merge($subscriber, [
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $id = $this->db->insert('bot_subscribers', [
            'bot_id' => $botId,
            'telegram_chat_id' => $chatId,
            'first_name' => $update['first_name'] ?? null,
            'last_name' => $update['last_name'] ?? null,
            'username' => $update['username'] ?? null,
            'status' => 'active',
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->fetch('SELECT * FROM bot_subscribers WHERE id = :id', ['id' => $id]);
    }

    private function getNode(int $nodeId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM bot_nodes WHERE id = :id AND bot_id = :bot_id LIMIT 1',
            ['id' => $nodeId, 'bot_id' => $this->bot['id']]
        );
    }

    private function findButtonById(int $buttonId): ?array
    {
        return $this->db->fetch(
            'SELECT bnb.* FROM bot_node_buttons bnb
             INNER JOIN bot_nodes bn ON bn.id = bnb.node_id
             WHERE bnb.id = :id AND bn.bot_id = :bot_id LIMIT 1',
            ['id' => $buttonId, 'bot_id' => $this->bot['id']]
        );
    }

    private function findKeywordMatch(int $botId, string $text): ?array
    {
        $keywords = $this->db->fetchAll(
            'SELECT * FROM bot_keywords WHERE bot_id = :bot_id',
            ['bot_id' => $botId]
        );

        $textLower = mb_strtolower(trim($text));

        foreach ($keywords as $keyword) {
            $kw = mb_strtolower((string) $keyword['keyword']);
            $matched = match ($keyword['match_type']) {
                'exact' => $textLower === $kw,
                'starts' => str_starts_with($textLower, $kw),
                default => str_contains($textLower, $kw),
            };

            if ($matched) {
                return $keyword;
            }
        }

        return null;
    }

    private function saveSubscriberField(int $subscriberId, string $fieldKey, string $value): void
    {
        $this->db->query(
            'INSERT INTO subscriber_fields (subscriber_id, field_key, field_value)
             VALUES (:subscriber_id, :field_key, :field_value)
             ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)',
            ['subscriber_id' => $subscriberId, 'field_key' => $fieldKey, 'field_value' => $value]
        );
    }

    public function addTagToSubscriber(int $subscriberId, int $tagId): void
    {
        $this->db->query(
            'INSERT IGNORE INTO subscriber_tags (subscriber_id, tag_id) VALUES (:subscriber_id, :tag_id)',
            ['subscriber_id' => $subscriberId, 'tag_id' => $tagId]
        );

        if (class_exists(\App\Services\SequenceTrigger::class)) {
            \App\Services\SequenceTrigger::onTagAdded($this->db, $subscriberId, $tagId);
        }
    }

    public function renderTemplate(string $text, array $subscriber): string
    {
        return \App\Services\TemplateRenderer::render($this->db, $text, $subscriber);
    }

    private function logMessage(int $botId, int $subscriberId, string $direction, string $type, ?string $content, array $rawPayload): void
    {
        $this->db->insert('bot_logs', [
            'bot_id' => $botId,
            'subscriber_id' => $subscriberId,
            'direction' => $direction,
            'message_type' => $type,
            'content' => $content,
            'raw_payload' => $rawPayload === [] ? null : json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function logError(string $message): void
    {
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/flow_engine_error.log', $line, FILE_APPEND);
    }
}
