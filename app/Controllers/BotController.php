<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Telegram\TelegramApi;

final class BotController extends Controller
{
    public function index(Request $request): void
    {
        $bots = $this->db->fetchAll(
            'SELECT * FROM bots WHERE owner_id = :id AND status != "deleted" ORDER BY created_at DESC',
            ['id' => Auth::id()]
        );

        $this->render('bots/index', ['title' => 'Botlarım', 'bots' => $bots]);
    }

    public function create(Request $request): void
    {
        $this->render('bots/create', ['title' => 'Yeni bot']);
    }

    public function store(Request $request): void
    {
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        $token = trim((string) $request->input('telegram_token', ''));

        if ($name === '' || $token === '') {
            $this->flash('error', 'Bot adı və Telegram token mütləqdir.');
            $this->redirect('/bots/create');
        }

        $tg = new TelegramApi($token);
        $me = $tg->getMe();

        $username = null;
        if ($me !== false && !empty($me['ok']) && isset($me['result']['username'])) {
            $username = $me['result']['username'];
        } elseif ($me !== false && !empty($me['result']['test_mode'])) {
            // test rejimində real Telegram cavabı yoxdur, fake username istifadə et
            $username = 'test_bot_' . substr(md5($token), 0, 6);
        } else {
            $this->flash('error', 'Telegram token doğrulanmadı. Tokeni yoxlayın.');
            $this->redirect('/bots/create');
        }

        $webhookSecret = bin2hex(random_bytes(24));

        $botId = $this->db->insert('bots', [
            'owner_id' => Auth::id(),
            'name' => $name,
            'telegram_token' => $token,
            'telegram_username' => $username,
            'webhook_secret' => $webhookSecret,
            'status' => 'active',
        ]);

        $webhookUrl = rtrim((string) config()['app']['url'], '/') . '/webhook.php?s=' . $webhookSecret;
        $result = $tg->setWebhook($webhookUrl, $webhookSecret);

        if ($result !== false && !empty($result['ok'])) {
            $this->flash('success', 'Bot yaradıldı və webhook uğurla quruldu (@' . $username . ').');
        } else {
            $this->flash('error', 'Bot yaradıldı, lakin webhook qurula bilmədi. Panel-dən yenidən cəhd edin.');
        }

        $this->redirect('/bots/' . $botId . '/nodes');
    }

    public function edit(string $id, Request $request): void
    {
        $bot = $this->ownedBot($id);
        $nodes = $this->db->fetchAll(
            'SELECT id, node_key, title FROM bot_nodes WHERE bot_id = :id ORDER BY node_key',
            ['id' => $bot['id']]
        );

        $this->render('bots/edit', ['title' => 'Bot ayarları', 'bot' => $bot, 'nodes' => $nodes]);
    }

    public function update(string $id, Request $request): void
    {
        $bot = $this->ownedBot($id);
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        $status = (string) $request->input('status', 'active');
        $startNodeId = $request->input('start_node_id', '');

        if (!in_array($status, ['active', 'paused'], true)) {
            $status = 'active';
        }

        $this->db->update('bots', [
            'name' => $name !== '' ? $name : $bot['name'],
            'status' => $status,
            'start_node_id' => $startNodeId !== '' ? (int) $startNodeId : null,
        ], 'id = :id AND owner_id = :owner_id', ['id' => $bot['id'], 'owner_id' => Auth::id()]);

        $this->flash('success', 'Bot yeniləndi.');
        $this->redirect('/bots/' . $bot['id'] . '/edit');
    }

    public function delete(string $id, Request $request): void
    {
        $bot = $this->ownedBot($id);
        $this->verifyCsrf($request);

        $tg = new TelegramApi((string) $bot['telegram_token']);
        $tg->deleteWebhook();

        $this->db->update('bots', ['status' => 'deleted'], 'id = :id AND owner_id = :owner_id', [
            'id' => $bot['id'],
            'owner_id' => Auth::id(),
        ]);

        $this->flash('success', 'Bot silindi.');
        $this->redirect('/bots');
    }
}
