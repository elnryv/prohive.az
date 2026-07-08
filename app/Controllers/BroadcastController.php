<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class BroadcastController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $broadcasts = $this->db->fetchAll(
            'SELECT * FROM broadcasts WHERE bot_id = :bot_id ORDER BY created_at DESC',
            ['bot_id' => $bot['id']]
        );
        $tags = $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $bot['id']]);

        $this->render('broadcasts/index', ['title' => 'Broadcast', 'bot' => $bot, 'broadcasts' => $broadcasts, 'tags' => $tags]);
    }

    public function create(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $tags = $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $bot['id']]);

        $this->render('broadcasts/create', ['title' => 'Yeni broadcast', 'bot' => $bot, 'tags' => $tags]);
    }

    public function store(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $text = trim((string) $request->input('message_text', ''));
        $targetTagId = $request->input('target_tag_id', '');

        if ($text === '') {
            $this->flash('error', 'Mesaj mətni boş ola bilməz.');
            $this->redirect('/bots/' . $bot['id'] . '/broadcasts/create');
        }

        $targetTagId = $targetTagId !== '' ? (int) $targetTagId : null;

        $broadcastId = $this->db->insert('broadcasts', [
            'bot_id' => $bot['id'],
            'message_text' => $text,
            'target_tag_id' => $targetTagId,
            'status' => 'queued',
        ]);

        if ($targetTagId !== null) {
            $subscribers = $this->db->fetchAll(
                "SELECT bs.id FROM bot_subscribers bs
                 INNER JOIN subscriber_tags st ON st.subscriber_id = bs.id
                 WHERE bs.bot_id = :bot_id AND st.tag_id = :tag_id AND bs.status = 'active'",
                ['bot_id' => $bot['id'], 'tag_id' => $targetTagId]
            );
        } else {
            $subscribers = $this->db->fetchAll(
                "SELECT id FROM bot_subscribers WHERE bot_id = :bot_id AND status = 'active'",
                ['bot_id' => $bot['id']]
            );
        }

        foreach ($subscribers as $subscriber) {
            $this->db->insert('broadcast_queue', [
                'broadcast_id' => $broadcastId,
                'subscriber_id' => $subscriber['id'],
                'status' => 'pending',
            ]);
        }

        $this->db->update('broadcasts', ['total_count' => count($subscribers)], 'id = :id', ['id' => $broadcastId]);

        $this->flash('success', sprintf('Broadcast yaradıldı, %d abunəçi növbəyə qoyuldu. Göndəriş cron worker vasitəsilə həyata keçəcək.', count($subscribers)));
        $this->redirect('/bots/' . $bot['id'] . '/broadcasts');
    }

    public function send(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $broadcast = $this->db->fetch(
            'SELECT * FROM broadcasts WHERE id = :id AND bot_id = :bot_id',
            ['id' => (int) $id, 'bot_id' => $bot['id']]
        );

        if ($broadcast === null) {
            http_response_code(404);
            echo '404 - Broadcast tapılmadı.';
            exit;
        }

        if (!in_array($broadcast['status'], ['sending', 'done'], true)) {
            $this->db->update('broadcasts', ['status' => 'queued'], 'id = :id', ['id' => $broadcast['id']]);
            $this->flash('success', 'Broadcast növbəyə qoyuldu, cron worker göndərəcək.');
        } else {
            $this->flash('error', 'Bu broadcast artıq göndərilib və ya göndərilir.');
        }

        $this->redirect('/bots/' . $bot['id'] . '/broadcasts');
    }
}
