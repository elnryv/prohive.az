<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class NodeController extends Controller
{
    private const TYPES = ['text', 'buttons', 'input', 'image', 'delay', 'goto', 'api', 'final'];

    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $nodes = $this->db->fetchAll(
            'SELECT * FROM bot_nodes WHERE bot_id = :id ORDER BY id ASC',
            ['id' => $bot['id']]
        );

        $this->render('nodes/index', ['title' => 'Node-lar', 'bot' => $bot, 'nodes' => $nodes]);
    }

    public function create(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $nodes = $this->allNodes((int) $bot['id']);
        $tags = $this->allTags((int) $bot['id']);

        $this->render('nodes/form', [
            'title' => 'Yeni node',
            'bot' => $bot,
            'node' => null,
            'buttons' => [],
            'nodes' => $nodes,
            'tags' => $tags,
            'types' => self::TYPES,
        ]);
    }

    public function store(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $data = $this->extractData($request);
        $nodeKey = trim((string) $request->input('node_key', ''));

        if ($nodeKey === '') {
            $this->flash('error', 'Node key mütləqdir.');
            $this->redirect('/bots/' . $bot['id'] . '/nodes/create');
        }

        $exists = $this->db->fetch(
            'SELECT id FROM bot_nodes WHERE bot_id = :bot_id AND node_key = :node_key',
            ['bot_id' => $bot['id'], 'node_key' => $nodeKey]
        );

        if ($exists !== null) {
            $this->flash('error', 'Bu node_key artıq mövcuddur, unikal olmalıdır.');
            $this->redirect('/bots/' . $bot['id'] . '/nodes/create');
        }

        $data['bot_id'] = $bot['id'];
        $data['node_key'] = $nodeKey;

        $nodeId = $this->db->insert('bot_nodes', $data);

        $this->flash('success', 'Node yaradıldı.');
        $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $nodeId . '/edit');
    }

    public function edit(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $node = $this->ownedNode($bot, $id);
        $buttons = $this->db->fetchAll(
            'SELECT * FROM bot_node_buttons WHERE node_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $node['id']]
        );

        $this->render('nodes/form', [
            'title' => 'Node redaktəsi',
            'bot' => $bot,
            'node' => $node,
            'buttons' => $buttons,
            'nodes' => $this->allNodes((int) $bot['id'], (int) $node['id']),
            'tags' => $this->allTags((int) $bot['id']),
            'types' => self::TYPES,
        ]);
    }

    public function update(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $node = $this->ownedNode($bot, $id);
        $this->verifyCsrf($request);

        $nodeKey = trim((string) $request->input('node_key', ''));
        if ($nodeKey === '') {
            $this->flash('error', 'Node key mütləqdir.');
            $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $node['id'] . '/edit');
        }

        $duplicate = $this->db->fetch(
            'SELECT id FROM bot_nodes WHERE bot_id = :bot_id AND node_key = :node_key AND id != :id',
            ['bot_id' => $bot['id'], 'node_key' => $nodeKey, 'id' => $node['id']]
        );
        if ($duplicate !== null) {
            $this->flash('error', 'Bu node_key artıq başqa node tərəfindən istifadə olunur.');
            $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $node['id'] . '/edit');
        }

        $data = $this->extractData($request);
        $data['node_key'] = $nodeKey;

        $this->db->update('bot_nodes', $data, 'id = :id AND bot_id = :bot_id', [
            'id' => $node['id'],
            'bot_id' => $bot['id'],
        ]);

        $this->flash('success', 'Node yeniləndi.');
        $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $node['id'] . '/edit');
    }

    public function delete(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $node = $this->ownedNode($bot, $id);
        $this->verifyCsrf($request);

        $this->db->query('DELETE FROM bot_nodes WHERE id = :id AND bot_id = :bot_id', [
            'id' => $node['id'],
            'bot_id' => $bot['id'],
        ]);

        $this->flash('success', 'Node silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/nodes');
    }

    public function storeButton(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $node = $this->ownedNode($bot, $id);
        $this->verifyCsrf($request);

        $buttonType = (string) $request->input('button_type', 'inline');
        $targetNodeId = $request->input('target_node_id', '');
        $actionTagId = $request->input('action_tag_id', '');

        $this->db->insert('bot_node_buttons', [
            'node_id' => $node['id'],
            'button_text' => (string) $request->input('button_text', ''),
            'button_type' => in_array($buttonType, ['inline', 'url'], true) ? $buttonType : 'inline',
            'target_node_id' => $targetNodeId !== '' ? (int) $targetNodeId : null,
            'url' => $request->input('url', '') !== '' ? (string) $request->input('url') : null,
            'action_tag_id' => $actionTagId !== '' ? (int) $actionTagId : null,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $this->flash('success', 'Düymə əlavə olundu.');
        $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $node['id'] . '/edit');
    }

    public function deleteButton(string $botId, string $id, string $buttonId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $node = $this->ownedNode($bot, $id);
        $this->verifyCsrf($request);

        $this->db->query(
            'DELETE FROM bot_node_buttons WHERE id = :id AND node_id = :node_id',
            ['id' => (int) $buttonId, 'node_id' => $node['id']]
        );

        $this->flash('success', 'Düymə silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/nodes/' . $node['id'] . '/edit');
    }

    private function ownedNode(array $bot, string $id): array
    {
        $node = $this->db->fetch(
            'SELECT * FROM bot_nodes WHERE id = :id AND bot_id = :bot_id LIMIT 1',
            ['id' => (int) $id, 'bot_id' => $bot['id']]
        );

        if ($node === null) {
            http_response_code(404);
            echo '404 - Node tapılmadı.';
            exit;
        }

        return $node;
    }

    private function allNodes(int $botId, ?int $excludeId = null): array
    {
        if ($excludeId !== null) {
            return $this->db->fetchAll(
                'SELECT id, node_key, title FROM bot_nodes WHERE bot_id = :bot_id AND id != :exclude ORDER BY node_key',
                ['bot_id' => $botId, 'exclude' => $excludeId]
            );
        }

        return $this->db->fetchAll(
            'SELECT id, node_key, title FROM bot_nodes WHERE bot_id = :bot_id ORDER BY node_key',
            ['bot_id' => $botId]
        );
    }

    private function allTags(int $botId): array
    {
        return $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $botId]);
    }

    private function extractData(Request $request): array
    {
        $type = (string) $request->input('node_type', 'text');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'text';
        }

        $nullableInt = static function (mixed $v): ?int {
            return $v !== null && $v !== '' ? (int) $v : null;
        };

        return [
            'title' => (string) $request->input('title', '') ?: null,
            'node_type' => $type,
            'message_text' => (string) $request->input('message_text', '') ?: null,
            'media_url' => (string) $request->input('media_url', '') ?: null,
            'input_field_key' => (string) $request->input('input_field_key', '') ?: null,
            'input_next_node' => $nullableInt($request->input('input_next_node')),
            'delay_seconds' => $nullableInt($request->input('delay_seconds')),
            'goto_node_id' => $nullableInt($request->input('goto_node_id')),
            'api_url' => (string) $request->input('api_url', '') ?: null,
            'api_method' => in_array($request->input('api_method'), ['GET', 'POST'], true)
                ? $request->input('api_method')
                : 'POST',
        ];
    }
}
