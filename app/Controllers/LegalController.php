<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

/**
 * Hüquqi sənədlər (Faza 7) — bax CLAUDE.md bölmə 1 "Hüquqi qeyd".
 * Mənbə: Birlikde_Huquqi_Paket.docx, QARALAMA statusunda (hüquqşünas təsdiqi
 * gözlənilir). Sənəd siyahısı config/legal/sujetler.php-də, hər sənədin HTML
 * məzmunu config/legal/content/{slug}.php-də saxlanılır.
 */
final class LegalController
{
    public function index(Request $request): mixed
    {
        $this->hazirlaDil($request);

        return View::render('legal/index', array_merge($this->navParams(), [
            'sujetler' => $this->sujetler(),
        ]));
    }

    public function goster(Request $request, array $params = []): mixed
    {
        $this->hazirlaDil($request);

        $slug = (string) ($params['slug'] ?? '');
        $sujetler = $this->sujetler();

        $secilmis = null;
        foreach ($sujetler as $sujet) {
            if ($sujet['slug'] === $slug) {
                $secilmis = $sujet;
                break;
            }
        }

        if ($secilmis === null) {
            Response::redirect('/huquqi');
        }

        $contentPath = dirname(__DIR__, 2) . '/config/legal/content/' . $slug . '.php';
        if (!is_file($contentPath)) {
            Response::redirect('/huquqi');
        }

        return View::render('legal/goster', array_merge($this->navParams(), [
            'sujetler' => $sujetler,
            'secilmis' => $secilmis,
            'metn' => require $contentPath,
        ]));
    }

    private function sujetler(): array
    {
        return require dirname(__DIR__, 2) . '/config/legal/sujetler.php';
    }

    private function hazirlaDil(Request $request): void
    {
        $userDil = null;
        $userId = Session::get('user_id');

        if ($userId !== null) {
            $user = (new User())->findById((int) $userId);
            $userDil = $user['dil'] ?? null;
        }

        Lang::resolve($request, $userDil);
    }

    private function navParams(): array
    {
        return [
            'girisEdilib' => Session::has('user_id'),
            'rol' => Session::get('rol'),
        ];
    }
}
