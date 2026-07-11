<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Services\AdminAuthService;

final class AdminAuthController
{
    public function giris(Request $request): mixed
    {
        try {
            $service = new AdminAuthService();
            $admin = $service->giris(
                (string) $request->input('telefon', ''),
                (string) $request->input('parol', ''),
                $request->ip()
            );

            return Response::json(['status' => 'ok', 'ad' => $admin['ad'], 'soyad' => $admin['soyad']]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 401);
        }
    }

    public function cixis(Request $request): mixed
    {
        (new AdminAuthService())->cixis();

        return Response::json(['status' => 'ok']);
    }

    public function parolDeyis(Request $request): mixed
    {
        try {
            $adminId = (int) Session::get('admin_id');
            (new AdminAuthService())->parolDeyis(
                $adminId,
                (string) $request->input('cari_parol', ''),
                (string) $request->input('yeni_parol', ''),
                $request->ip()
            );

            return Response::json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }
}
