<?php
declare(strict_types=1);

/**
 * CLI cron worker: hər dəqiqə işə düşür.
 * status=queued/sending olan broadcast-ları götürür, broadcast_queue-dan
 * pending sətirləri 30-luq partiyalarla göndərir.
 * crontab: * * * * * php /var/www/bot.birlikde.biz/cron/broadcast_worker.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Services\TemplateRenderer;
use App\Telegram\TelegramApi;

const BATCH_SIZE = 30;

function log_line(string $message): void
{
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents(__DIR__ . '/../storage/logs/broadcast_worker.log', $line, FILE_APPEND);
    echo $line;
}

$db = Database::getInstance();

$broadcasts = $db->fetchAll("SELECT * FROM broadcasts WHERE status IN ('queued', 'sending') ORDER BY created_at ASC");

if ($broadcasts === []) {
    log_line('Göndəriləcək broadcast yoxdur.');
    exit(0);
}

foreach ($broadcasts as $broadcast) {
    if ($broadcast['status'] === 'queued') {
        $db->update('broadcasts', ['status' => 'sending'], 'id = :id', ['id' => $broadcast['id']]);
    }

    $bot = $db->fetch('SELECT * FROM bots WHERE id = :id', ['id' => $broadcast['bot_id']]);
    if ($bot === null) {
        $db->update('broadcasts', ['status' => 'failed'], 'id = :id', ['id' => $broadcast['id']]);
        continue;
    }

    $tg = new TelegramApi((string) $bot['telegram_token']);

    $queueRows = $db->fetchAll(
        "SELECT bq.*, bs.telegram_chat_id, bs.first_name, bs.last_name, bs.username, bs.id AS subscriber_id
         FROM broadcast_queue bq
         INNER JOIN bot_subscribers bs ON bs.id = bq.subscriber_id
         WHERE bq.broadcast_id = :broadcast_id AND bq.status = 'pending'
         LIMIT " . BATCH_SIZE,
        ['broadcast_id' => $broadcast['id']]
    );

    foreach ($queueRows as $row) {
        $subscriber = [
            'id' => $row['subscriber_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'username' => $row['username'],
        ];

        $text = TemplateRenderer::render($db, (string) $broadcast['message_text'], $subscriber);
        $result = $tg->sendMessage((int) $row['telegram_chat_id'], $text);

        if ($result !== false && !empty($result['ok'])) {
            $db->update('broadcast_queue', ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
            $db->query('UPDATE broadcasts SET sent_count = sent_count + 1 WHERE id = :id', ['id' => $broadcast['id']]);
        } else {
            $db->update('broadcast_queue', ['status' => 'failed', 'error' => 'send failed'], 'id = :id', ['id' => $row['id']]);
            $db->query('UPDATE broadcasts SET failed_count = failed_count + 1 WHERE id = :id', ['id' => $broadcast['id']]);
        }

        // Telegram rate limit-inə hörmət: saniyədə ~25 mesaj.
        usleep(45000);
    }

    log_line(sprintf('Broadcast #%d: %d mesaj emal olundu.', $broadcast['id'], count($queueRows)));

    $remaining = (int) $db->fetch(
        "SELECT COUNT(*) AS c FROM broadcast_queue WHERE broadcast_id = :id AND status = 'pending'",
        ['id' => $broadcast['id']]
    )['c'];

    if ($remaining === 0) {
        $db->update('broadcasts', ['status' => 'done'], 'id = :id', ['id' => $broadcast['id']]);
        log_line(sprintf('Broadcast #%d tamamlandı.', $broadcast['id']));
    }
}
