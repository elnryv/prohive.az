<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\View;
use App\Core\WebPush;

/** Push kampaniya paneli (bölmə 9.7). */
final class CampaignController
{
    public function index(): void
    {
        AdminAuth::requireLogin('/giris');
        View::render('admin/campaign', ['pageTitle' => 'Push kampaniya', 'result' => null], 'layouts/admin');
    }

    public function send(): void
    {
        AdminAuth::requireLogin('/giris');
        if (!Csrf::verifyRequest()) {
            http_response_code(419);
            echo 'CSRF token etibarsızdır.';
            return;
        }

        $target = (string) ($_POST['target'] ?? 'all_drivers');
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($title === '' || $body === '') {
            View::render('admin/campaign', ['pageTitle' => 'Push kampaniya', 'result' => 'Başlıq və mətn tələb olunur'], 'layouts/admin');
            return;
        }

        $userIds = match ($target) {
            'all_drivers' => array_column(DB::conn()->query("SELECT id FROM users WHERE role='driver' AND driver_status='approved'")->fetchAll(), 'id'),
            'all_customers' => array_column(DB::conn()->query("SELECT id FROM users WHERE role='customer'")->fetchAll(), 'id'),
            'route_subscribers' => array_column(DB::conn()->query('SELECT DISTINCT driver_id as id FROM route_subscriptions')->fetchAll(), 'id'),
            default => [],
        };
        $userIds = array_map('intval', $userIds);

        WebPush::sendToUsersLocalized($userIds, static fn () => [
            'title' => $title,
            'body' => $body,
            'url' => '/',
        ]);

        AdminAuth::log('push_campaign', null, null, ['target' => $target, 'recipients' => count($userIds), 'title' => $title]);

        View::render('admin/campaign', [
            'pageTitle' => 'Push kampaniya',
            'result' => count($userIds) . ' istifadəçiyə göndərildi (növbə ilə, 50-lik partiyalarla).',
        ], 'layouts/admin');
    }
}
