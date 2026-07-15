<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Rayon;
use App\Models\Sehir;

/**
 * Müştəri/kuryer tərəfi üçün ictimai (auth tələb etməyən) ərazi axtarışı —
 * bax bölmə 5.2 (Ünvan Daxiletmə). Admin-in idarəetmə versiyası ayrıdır
 * (bax AdminEraziController — bütün əraziləri göstərir, bu isə yalnız aktiv olanları).
 */
final class EraziController
{
    public function sehirler(Request $request): mixed
    {
        return Response::json(['status' => 'ok', 'data' => (new Sehir())->aktivListAll()]);
    }

    public function rayonlar(Request $request, array $params): mixed
    {
        $data = (new Rayon())->aktivListBySehir((int) $params['sehirId']);

        return Response::json(['status' => 'ok', 'data' => $data]);
    }
}
