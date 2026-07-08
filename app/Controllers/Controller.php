<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

abstract class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function render(string $view, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';

        ob_start();
        Response::view($viewPath, $data);
        $content = ob_get_clean();

        $layoutData = array_merge($data, [
            'content' => $content,
            'currentUser' => Auth::user(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
        ]);

        Response::view(dirname(__DIR__) . '/Views/layout.php', $layoutData);
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function pullFlash(string $type): ?string
    {
        $key = 'flash_' . $type;
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $message;
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
        exit;
    }

    protected function verifyCsrf(Request $request): void
    {
        if (!Csrf::verify($request->input('csrf_token'))) {
            $this->flash('error', 'Sessiya vaxtı bitib, yenidən cəhd edin (CSRF).');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    /**
     * Botu tapır və giriş etmiş istifadəçiyə aid olduğunu yoxlayır (tenant izolyasiyası).
     * Aid deyilsə 404 ilə dayandırır.
     */
    protected function ownedBot(int|string $botId): array
    {
        $user = Auth::user();
        $bot = $this->db->fetch(
            "SELECT * FROM bots WHERE id = :id AND status != 'deleted' LIMIT 1",
            ['id' => (int) $botId]
        );

        if ($bot === null || (!$user['is_super'] && (int) $bot['owner_id'] !== (int) $user['id'])) {
            http_response_code(404);
            echo '404 - Bot tapılmadı və ya sizə aid deyil.';
            exit;
        }

        return $bot;
    }
}
