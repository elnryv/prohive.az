<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class FieldController extends Controller
{
    public function index(string $botId, Request $request): void
    {
        $bot = $this->ownedBot($botId);

        $fields = $this->db->fetchAll(
            'SELECT sf.field_key, COUNT(*) AS usage_count
             FROM subscriber_fields sf
             INNER JOIN bot_subscribers bs ON bs.id = sf.subscriber_id
             WHERE bs.bot_id = :bot_id
             GROUP BY sf.field_key
             ORDER BY sf.field_key',
            ['bot_id' => $bot['id']]
        );

        $this->render('fields/index', ['title' => 'Custom sahələr', 'bot' => $bot, 'fields' => $fields]);
    }
}
