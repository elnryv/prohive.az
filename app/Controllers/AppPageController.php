<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Core\ValidationException;
use App\Models\Kurye;
use App\Models\User;
use App\Models\YukdasimaOlcusu;
use App\Services\OdenisService;

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

    /**
     * Telefon-əsaslı ağıllı giriş/qeydiyyat axını — istifadəçi əvvəlcə yalnız
     * telefon nömrəsini daxil edir (POST /telefon-yoxla ilə mövcudluq
     * client-tərəfdə yoxlanılır), sonra ya "Parol" (giriş), ya da qeydiyyat
     * sahələri (ad/soyad/rol və s.) eyni ekranda animasiyalı açılır — bax
     * auth/giris.php `Birlikde.initAuthWizard()`. Bu səbəbdən qeydiyyat üçün
     * lazım olan məlumat (ölçülər, sözləşmə mətni) da burada hazırlanır.
     */
    public function giris(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['olculer'] = (new YukdasimaOlcusu())->all();
        $params['sozlesmeMetni'] = $this->sozlesmeMetni();

        return View::render('auth/giris', $params);
    }

    /**
     * Ayrı qeydiyyat ekranı artıq yoxdur (yuxarıdakı qeydə bax) — köhnə
     * linklər/bookmark-lar üçün /giris-ə yönləndirilir.
     */
    public function qeydiyyat(Request $request): mixed
    {
        $this->hazirlaDil($request);

        Response::redirect('/giris');
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

        return View::render('kurye/lovhe', $this->navParams());
    }

    public function profil(Request $request): mixed
    {
        $this->hazirlaDil($request);

        return View::render('kurye/profil', $this->navParams());
    }

    public function sifarislerim(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['whatsappSupport'] = Env::get('WHATSAPP_SUPPORT_NUMBER', '');

        return View::render('kurye/sifarislerim', $params);
    }

    /**
     * Ödənişdən sonra Payriff-in kuryerin brauzerini yönləndirdiyi təsdiq
     * səhifəsi — bax OdenisController qeydi.
     */
    public function odenisQayit(Request $request): mixed
    {
        $this->hazirlaDil($request);

        $params = $this->navParams();
        $params['ilkinHal'] = null;

        $userId = Session::get('user_id');
        if ($userId !== null) {
            $kurye = (new Kurye())->findByUserId((int) $userId);
            if ($kurye !== null) {
                try {
                    $params['ilkinHal'] = (new OdenisService())->sonHal((int) $kurye['id']);
                } catch (ValidationException) {
                    $params['ilkinHal'] = null;
                }
            }
        }

        return View::render('odenis/qayit', $params);
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
