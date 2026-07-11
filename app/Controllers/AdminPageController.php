<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * appadmin.birlikde.biz HTML səhifələri (unauthenticated shell — həqiqi giriş
 * yoxlaması client tərəfdə, JSON API-lərin 401 cavabı ilə edilir).
 */
final class AdminPageController
{
    public function landing(Request $request): mixed
    {
        Lang::resolve($request);
        Response::redirect(Session::has('admin_id') ? '/panel' : '/giris');
    }

    public function giris(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/giris', []);
    }

    public function dashboard(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/dashboard', []);
    }

    public function musteriler(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/musteriler', []);
    }

    public function kuryerler(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/kuryerler', []);
    }

    public function sifarisler(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/sifarisler', []);
    }

    public function bannerler(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/bannerler', []);
    }

    public function erazi(Request $request): mixed
    {
        Lang::resolve($request);

        return View::render('admin/erazi', []);
    }
}
