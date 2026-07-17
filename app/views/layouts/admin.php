<?php
/** @var string $content */
/** @var string|null $pageTitle */
use App\Core\AdminAuth;
use App\Core\Csrf;

$title = isset($pageTitle) ? $pageTitle . ' · İdarəetmə mərkəzi' : 'İdarəetmə mərkəzi — Birlikdə Yük';
$admin = AdminAuth::admin();
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';

// Bütün admin bölmələri tək yandan açılan drawer-də (bölmə: admin naviqasiya v2).
$nav = [
    '/dashboard' => ['home', 'Dashboard'],
    '/surucular' => ['truck', 'Sürücülər'],
    '/elanlar' => ['box', 'Elanlar'],
    '/odenisler' => ['wallet', 'Ödənişlər'],
    '/musteriler' => ['user', 'Müştərilər'],
    '/parametrler' => ['settings', 'Parametrlər'],
    '/kampaniya' => ['bell', 'Push kampaniya'],
    '/loglar' => ['list', 'Loglar'],
];
$currentLabel = $nav[$path][1] ?? 'Dashboard';
// app.css/admin.css üçün keş-sındırma (bax layouts/app.php) — public_admin öz ayrıca
// kopyasını saxladığı üçün (bölmə: aaPanel qeydi) bu fayllar `cp` ilə sinxronlaşdıqda
// belə Nginx-in 12 saatlıq statik keşi köhnə versiyanı saxlaya bilməsin deyə vacibdir.
$docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$cssVer = @filemtime($docRoot . '/assets/css/app.css') ?: time();
$adminCssVer = @filemtime($docRoot . '/assets/css/admin.css') ?: time();
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= e($title) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= $cssVer ?>">
<link rel="stylesheet" href="/assets/css/admin.css?v=<?= $adminCssVer ?>">
</head>
<body data-auth="0">
<?php if ($admin !== null): ?>
<div class="admin-shell">
  <input type="checkbox" id="admin-drawer-toggle" class="admin-drawer-toggle">

  <header class="admin-topbar">
    <label for="admin-drawer-toggle" class="admin-hamburger-btn" aria-label="Menyu"><?= icon('list', 'icon', 22) ?></label>
    <span class="admin-topbar-title"><?= e($currentLabel) ?></span>
  </header>

  <label for="admin-drawer-toggle" class="admin-drawer-backdrop" aria-hidden="true"></label>

  <aside class="admin-drawer">
    <div class="admin-drawer-header">
      <span class="brand"><img class="brand-logo" src="/assets/icons/icon-192.png" alt="">Birlikdə <span class="amber">Yük</span></span>
      <label for="admin-drawer-toggle" class="admin-drawer-close" aria-label="Bağla"><?= icon('close', 'icon', 18) ?></label>
    </div>
    <p class="text-soft" style="padding:0 16px 12px;font-size:12px">İdarəetmə mərkəzi</p>
    <nav class="admin-drawer-nav">
      <?php foreach ($nav as $href => [$iconName, $label]): ?>
        <a href="<?= $href ?>" class="<?= $path === $href ? 'active' : '' ?>"><?= icon($iconName, 'icon', 18) ?> <span><?= e($label) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <form method="post" action="/cixis" class="admin-drawer-logout">
      <?= Csrf::field() ?>
      <button type="submit"><?= icon('logout', 'icon', 18) ?> <span>Çıxış</span></button>
    </form>
  </aside>

  <main class="admin-main">
    <?= $content ?>
  </main>
</div>
<?php else: ?>
  <?= $content ?>
<?php endif; ?>
</body>
</html>
