<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class SequenceController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);

        $sequences = $this->db->fetchAll(
            'SELECT * FROM sequences WHERE bot_id = :bot_id ORDER BY created_at DESC',
            ['bot_id' => $bot['id']]
        );

        foreach ($sequences as &$sequence) {
            $sequence['messages'] = $this->db->fetchAll(
                'SELECT * FROM sequence_messages WHERE sequence_id = :id ORDER BY sort_order ASC, id ASC',
                ['id' => $sequence['id']]
            );
        }
        unset($sequence);

        $tags = $this->db->fetchAll('SELECT id, name FROM tags WHERE bot_id = :bot_id ORDER BY name', ['bot_id' => $bot['id']]);

        $this->render('sequences/index', [
            'title' => 'Sequence-lər',
            'bot' => $bot,
            'sequences' => $sequences,
            'tags' => $tags,
        ]);
    }

    public function store(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        $triggerTagId = $request->input('trigger_tag_id', '');

        if ($name === '') {
            $this->flash('error', 'Sequence adı boş ola bilməz.');
            $this->redirect('/bots/' . $bot['id'] . '/sequences');
        }

        $this->db->insert('sequences', [
            'bot_id' => $bot['id'],
            'name' => $name,
            'trigger_tag_id' => $triggerTagId !== '' ? (int) $triggerTagId : null,
            'status' => 'active',
        ]);

        $this->flash('success', 'Sequence yaradıldı.');
        $this->redirect('/bots/' . $bot['id'] . '/sequences');
    }

    public function storeMessage(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $sequence = $this->ownedSequence($bot, $id);
        $this->verifyCsrf($request);

        $text = trim((string) $request->input('message_text', ''));
        if ($text === '') {
            $this->flash('error', 'Mesaj mətni boş ola bilməz.');
            $this->redirect('/bots/' . $bot['id'] . '/sequences');
        }

        $this->db->insert('sequence_messages', [
            'sequence_id' => $sequence['id'],
            'delay_hours' => (int) $request->input('delay_hours', 24),
            'message_text' => $text,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        $this->flash('success', 'Mesaj sequence-ə əlavə olundu.');
        $this->redirect('/bots/' . $bot['id'] . '/sequences');
    }

    public function deleteMessage(string $botId, string $id, string $messageId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $sequence = $this->ownedSequence($bot, $id);
        $this->verifyCsrf($request);

        $this->db->query(
            'DELETE FROM sequence_messages WHERE id = :id AND sequence_id = :seq_id',
            ['id' => (int) $messageId, 'seq_id' => $sequence['id']]
        );

        $this->flash('success', 'Mesaj silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/sequences');
    }

    private function ownedSequence(array $bot, string $id): array
    {
        $sequence = $this->db->fetch(
            'SELECT * FROM sequences WHERE id = :id AND bot_id = :bot_id',
            ['id' => (int) $id, 'bot_id' => $bot['id']]
        );

        if ($sequence === null) {
            http_response_code(404);
            echo '404 - Sequence tapılmadı.';
            exit;
        }

        return $sequence;
    }
}
