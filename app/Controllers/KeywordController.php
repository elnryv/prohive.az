<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class KeywordController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $keywords = $this->db->fetchAll(
            'SELECT bk.*, bn.node_key, bn.title AS node_title
             FROM bot_keywords bk
             INNER JOIN bot_nodes bn ON bn.id = bk.target_node_id
             WHERE bk.bot_id = :bot_id ORDER BY bk.created_at DESC',
            ['bot_id' => $bot['id']]
        );
        $nodes = $this->db->fetchAll('SELECT id, node_key, title FROM bot_nodes WHERE bot_id = :bot_id ORDER BY node_key', ['bot_id' => $bot['id']]);

        $this->render('keywords/index', ['title' => 'Açar sözlər', 'bot' => $bot, 'keywords' => $keywords, 'nodes' => $nodes]);
    }

    public function store(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $keyword = trim((string) $request->input('keyword', ''));
        $matchType = (string) $request->input('match_type', 'contains');
        $targetNodeId = $request->input('target_node_id', '');

        if ($keyword === '' || $targetNodeId === '') {
            $this->flash('error', 'Açar söz və hədəf node mütləqdir.');
            $this->redirect('/bots/' . $bot['id'] . '/keywords');
        }

        if (!in_array($matchType, ['exact', 'contains', 'starts'], true)) {
            $matchType = 'contains';
        }

        $node = $this->db->fetch('SELECT id FROM bot_nodes WHERE id = :id AND bot_id = :bot_id', [
            'id' => (int) $targetNodeId,
            'bot_id' => $bot['id'],
        ]);

        if ($node === null) {
            $this->flash('error', 'Hədəf node bu bota aid deyil.');
            $this->redirect('/bots/' . $bot['id'] . '/keywords');
        }

        $this->db->insert('bot_keywords', [
            'bot_id' => $bot['id'],
            'keyword' => $keyword,
            'match_type' => $matchType,
            'target_node_id' => (int) $targetNodeId,
        ]);

        $this->flash('success', 'Açar söz əlavə olundu.');
        $this->redirect('/bots/' . $bot['id'] . '/keywords');
    }

    public function delete(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $this->db->query('DELETE FROM bot_keywords WHERE id = :id AND bot_id = :bot_id', [
            'id' => (int) $id,
            'bot_id' => $bot['id'],
        ]);

        $this->flash('success', 'Açar söz silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/keywords');
    }
}
