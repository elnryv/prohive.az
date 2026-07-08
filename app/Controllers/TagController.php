<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class TagController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $tags = $this->db->fetchAll('SELECT * FROM tags WHERE bot_id = :id ORDER BY name', ['id' => $bot['id']]);

        $this->render('tags/index', ['title' => 'Tag-lar', 'bot' => $bot, 'tags' => $tags]);
    }

    public function store(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $this->flash('error', 'Tag adı boş ola bilməz.');
            $this->redirect('/bots/' . $bot['id'] . '/tags');
        }

        $exists = $this->db->fetch(
            'SELECT id FROM tags WHERE bot_id = :bot_id AND name = :name',
            ['bot_id' => $bot['id'], 'name' => $name]
        );

        if ($exists === null) {
            $this->db->insert('tags', ['bot_id' => $bot['id'], 'name' => $name]);
            $this->flash('success', 'Tag əlavə olundu.');
        } else {
            $this->flash('error', 'Bu tag artıq mövcuddur.');
        }

        $this->redirect('/bots/' . $bot['id'] . '/tags');
    }

    public function delete(string $botId, string $id, Request $request): void
    {
        $bot = $this->ownedBot($botId);
        $this->verifyCsrf($request);

        $this->db->query('DELETE FROM tags WHERE id = :id AND bot_id = :bot_id', [
            'id' => (int) $id,
            'bot_id' => $bot['id'],
        ]);

        $this->flash('success', 'Tag silindi.');
        $this->redirect('/bots/' . $bot['id'] . '/tags');
    }
}
