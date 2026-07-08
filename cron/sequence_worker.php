<?php
declare(strict_types=1);

/**
 * CLI cron worker: hər 5 dəqiqədə bir işə düşür.
 * subscriber_sequences-dən vaxtı çatan sətirləri götürür, cari mesajı göndərir,
 * növbəti mesaja keçir və ya sequence-i tamamlayır.
 * crontab: (hər 5 dəqiqə) php /var/www/bot.birlikde.biz/cron/sequence_worker.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Services\TemplateRenderer;
use App\Telegram\TelegramApi;

function log_line(string $message): void
{
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents(__DIR__ . '/../storage/logs/sequence_worker.log', $line, FILE_APPEND);
    echo $line;
}

$db = Database::getInstance();

$due = $db->fetchAll(
    "SELECT ss.*, bs.telegram_chat_id, bs.first_name, bs.last_name, bs.username, bs.bot_id
     FROM subscriber_sequences ss
     INNER JOIN bot_subscribers bs ON bs.id = ss.subscriber_id
     WHERE ss.status = 'running' AND ss.next_send_at IS NOT NULL AND ss.next_send_at <= NOW()"
);

if ($due === []) {
    log_line('Göndəriləcək sequence mesajı yoxdur.');
    exit(0);
}

$botCache = [];
$tgCache = [];

foreach ($due as $row) {
    if (empty($row['next_message_id'])) {
        $db->update('subscriber_sequences', ['status' => 'completed'], 'id = :id', ['id' => $row['id']]);
        continue;
    }

    $message = $db->fetch('SELECT * FROM sequence_messages WHERE id = :id', ['id' => $row['next_message_id']]);
    if ($message === null) {
        $db->update('subscriber_sequences', ['status' => 'completed'], 'id = :id', ['id' => $row['id']]);
        continue;
    }

    $botId = (int) $row['bot_id'];
    if (!isset($botCache[$botId])) {
        $botCache[$botId] = $db->fetch('SELECT * FROM bots WHERE id = :id', ['id' => $botId]);
        $tgCache[$botId] = new TelegramApi((string) $botCache[$botId]['telegram_token']);
    }

    $subscriber = [
        'id' => $row['subscriber_id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'username' => $row['username'],
    ];

    $text = TemplateRenderer::render($db, (string) $message['message_text'], $subscriber);
    $tgCache[$botId]->sendMessage((int) $row['telegram_chat_id'], $text);

    $nextMessage = $db->fetch(
        'SELECT * FROM sequence_messages WHERE sequence_id = :seq_id AND sort_order > :sort_order
         ORDER BY sort_order ASC, id ASC LIMIT 1',
        ['seq_id' => $row['sequence_id'], 'sort_order' => $message['sort_order']]
    );

    if ($nextMessage !== null) {
        $nextSendAt = date('Y-m-d H:i:s', strtotime('+' . (int) $nextMessage['delay_hours'] . ' hours'));
        $db->update('subscriber_sequences', [
            'next_message_id' => $nextMessage['id'],
            'next_send_at' => $nextSendAt,
        ], 'id = :id', ['id' => $row['id']]);
    } else {
        $db->update('subscriber_sequences', [
            'status' => 'completed',
            'next_message_id' => null,
            'next_send_at' => null,
        ], 'id = :id', ['id' => $row['id']]);
    }

    log_line(sprintf('subscriber_sequence #%d: mesaj #%d göndərildi.', $row['id'], $message['id']));
}
