<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BannerService;

/**
 * Müştəri/kuryer/yükdaşıma tətbiqindəki reklam banner karuseli — bax
 * musteri/panel.php, kurye/lovhe.php. Admin idarəetməsi üçün
 * AdminBannerController-ə bax.
 */
final class BannerController
{
    public function aktivOlanlar(Request $request): mixed
    {
        $rol = (string) (Session::get('rol') ?? 'musteri');
        $bannerler = (new BannerService())->aktivOlanlar($rol);

        return Response::json(['status' => 'ok', 'data' => $bannerler]);
    }
}
