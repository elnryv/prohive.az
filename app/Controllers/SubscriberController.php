<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class SubscriberController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $search = trim((string) $request->query('q', ''));
        $tagId = $request->query('tag_id', '');

        $sql = 'SELECT DISTINCT bs.* FROM bot_subscribers bs';
        $params = ['bot_id' => $bot['id']];

        if ($tagId !== '') {
            $sql .= ' INNER JOIN subscriber_tags st ON st.subscriber_id = bs.id AND st.tag_id = :tag_id';
            $params['tag_id'] = (int) $tagId;
        }

        $sql .= ' WHERE bs.bot_id = :bot_id';

        if ($search !== '') {
            $sql .= ' AND (bs.first_name LIKE :q OR bs.last_name LIKE :q OR bs.username LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY bs.subscribed_at DESC LIMIT 200';

        $subscribers = $this->db->fetchAll($sql, $params);
        $tags = $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $bot['id']]);

        $this->render('subscribers/index', [
            'title' => 'Abunəçilər',
            'bot' => $bot,
            'subscribers' => $subscribers,
            'tags' => $tags,
            'search' => $search,
            'selectedTagId' => $tagId,
        ]);
    }

    public function show(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $subscriber = $this->ownedSubscriber($bot, $id);

        $fields = $this->db->fetchAll(
            'SELECT * FROM subscriber_fields WHERE subscriber_id = :id ORDER BY field_key',
            ['id' => $subscriber['id']]
        );

        $tags = $this->db->fetchAll(
            'SELECT t.* FROM tags t INNER JOIN subscriber_tags st ON st.tag_id = t.id WHERE st.subscriber_id = :id ORDER BY t.name',
            ['id' => $subscriber['id']]
        );

        $allTags = $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $bot['id']]);

        $currentNode = $subscriber['current_node_id']
            ? $this->db->fetch('SELECT * FROM bot_nodes WHERE id = :id', ['id' => $subscriber['current_node_id']])
            : null;

        $logs = $this->db->fetchAll(
            'SELECT * FROM bot_logs WHERE subscriber_id = :id ORDER BY created_at DESC LIMIT 100',
            ['id' => $subscriber['id']]
        );

        $this->render('subscribers/show', [
            'title' => 'Abunəçi',
            'bot' => $bot,
            'subscriber' => $subscriber,
            'fields' => $fields,
            'tags' => $tags,
            'allTags' => $allTags,
            'currentNode' => $currentNode,
            'logs' => $logs,
        ]);
    }

    public function addTag(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $subscriber = $this->ownedSubscriber($bot, $id);
        $this->verifyCsrf($request);

        $tagId = $request->input('tag_id', '');
        $tagName = trim((string) $request->input('tag_name', ''));

        if ($tagId === '' && $tagName !== '') {
            $existing = $this->db->fetch(
                'SELECT id FROM tags WHERE bot_id = :bot_id AND name = :name',
                ['bot_id' => $bot['id'], 'name' => $tagName]
            );
            $tagId = $existing !== null ? $existing['id'] : $this->db->insert('tags', ['bot_id' => $bot['id'], 'name' => $tagName]);
        }

        if ($tagId !== '' && $tagId !== null) {
            $tag = $this->db->fetch('SELECT id FROM tags WHERE id = :id AND bot_id = :bot_id', ['id' => (int) $tagId, 'bot_id' => $bot['id']]);
            if ($tag !== null) {
                $this->db->query(
                    'INSERT IGNORE INTO subscriber_tags (subscriber_id, tag_id) VALUES (:sub_id, :tag_id)',
                    ['sub_id' => $subscriber['id'], 'tag_id' => $tag['id']]
                );

                if (class_exists(\App\Services\SequenceTrigger::class)) {
                    \App\Services\SequenceTrigger::onTagAdded($this->db, (int) $subscriber['id'], (int) $tag['id']);
                }

                $this->flash('success', 'Tag əlavə olundu.');
            }
        }

        $this->redirect('/bots/' . $bot['id'] . '/subscribers/' . $subscriber['id']);
    }

    public function removeTag(string $botId, string $id, string $tagId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $subscriber = $this->ownedSubscriber($bot, $id);
        $this->verifyCsrf($request);

        $this->db->query(
            'DELETE st FROM subscriber_tags st
             INNER JOIN tags t ON t.id = st.tag_id
             WHERE st.subscriber_id = :sub_id AND st.tag_id = :tag_id AND t.bot_id = :bot_id',
            ['sub_id' => $subscriber['id'], 'tag_id' => (int) $tagId, 'bot_id' => $bot['id']]
        );

        $this->flash('success', 'Tag silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/subscribers/' . $subscriber['id']);
    }

    private function ownedSubscriber(array $bot, string $id): array
    {
        $subscriber = $this->db->fetch(
            'SELECT * FROM bot_subscribers WHERE id = :id AND bot_id = :bot_id LIMIT 1',
            ['id' => (int) $id, 'bot_id' => $bot['id']]
        );

        if ($subscriber === null) {
            http_response_code(404);
            echo '404 - Abunəçi tapılmadı.';
            exit;
        }

        return $subscriber;
    }
}
