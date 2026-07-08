<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $userId = Auth::id();

        $botCount = (int) $this->db->fetch(
            'SELECT COUNT(*) AS c FROM bots WHERE owner_id = :id AND status != "deleted"',
            ['id' => $userId]
        )['c'];

        $subscriberCount = (int) ($this->db->fetch(
            "SELECT COUNT(*) AS c FROM bot_subscribers bs
             INNER JOIN bots b ON b.id = bs.bot_id
             WHERE b.owner_id = :id AND b.status != 'deleted'",
            ['id' => $userId]
        )['c'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT DATE(bl.created_at) AS day, COUNT(*) AS total
             FROM bot_logs bl
             INNER JOIN bots b ON b.id = bl.bot_id
             WHERE b.owner_id = :id AND bl.created_at >= (CURDATE() - INTERVAL 6 DAY)
             GROUP BY DATE(bl.created_at)
             ORDER BY day ASC",
            ['id' => $userId]
        );

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['day']] = (int) $r['total'];
        }

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} day"));
            $chart[] = ['day' => $day, 'total' => $byDay[$day] ?? 0];
        }

        $bots = $this->db->fetchAll(
            'SELECT id, name, status FROM bots WHERE owner_id = :id AND status != "deleted" ORDER BY created_at DESC LIMIT 5',
            ['id' => $userId]
        );

        $this->render('dashboard/index', [
            'title' => 'İdarə paneli',
            'botCount' => $botCount,
            'subscriberCount' => $subscriberCount,
            'chart' => $chart,
            'bots' => $bots,
        ]);
    }
}
