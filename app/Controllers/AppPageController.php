<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;
use App\Models\YukdasimaOlcusu;

/**
 * app.birlikde.biz HTML səhifələri (unauthenticated shell — həqiqi giriş
 * yoxlaması client tərəfdə, JSON API-lərin 401 cavabı ilə edilir). Bax
 * PROGRESS.md Faza 6 qeydi.
 */
final class AppPageController
{
    public function landing(Request $request): mixed
    {
        $this->hazirlaDil($request);

        if (Session::has('user_id')) {
            $rol = (string) Session::get('rol');
            Response::redirect($rol === 'musteri' ? '/panel' : '/lovhe');
        }

        Response::redirect('/giris');
    }

    public function giris(Request $request): mixed
    {
        $this->hazirlaDil($request);

        return View::render('auth/giris', $this->navParams());
    }

    public function qeydiyyat(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['olculer'] = (new YukdasimaOlcusu())->all();
        $params['sozlesmeMetni'] = $this->sozlesmeMetni();

        return View::render('auth/qeydiyyat', $params);
    }

    public function panel(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['olculer'] = (new YukdasimaOlcusu())->all();
        $params['whatsappSupport'] = Env::get('WHATSAPP_SUPPORT_NUMBER', '');

        return View::render('musteri/panel', $params);
    }

    public function lovhe(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['whatsappSupport'] = Env::get('WHATSAPP_SUPPORT_NUMBER', '');

        return View::render('kurye/lovhe', $params);
    }

    public function profil(Request $request): mixed
    {
        $this->hazirlaDil($request);

        return View::render('kurye/profil', $this->navParams());
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

    private function sozlesmeMetni(): string
    {
        $metinler = require dirname(__DIR__, 2) . '/config/sozlesme.php';
        $dil = Lang::current();

        return $metinler[$dil] ?? $metinler['az'] ?? '';
    }
}
