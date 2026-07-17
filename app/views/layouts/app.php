<?php
/** @var string $content */
/** @var string|null $pageTitle */
/** @var bool $noindex Default true — yalnız ana səhifə (bölmə 12.3) açıq şəkildə false ötürür. */
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Auth;

$lang = Lang::current();
$title = isset($pageTitle) ? $pageTitle . ' · ' . t('app_name') : t('app_name') . ' — ' . t('home.title');
$user = Auth::user();
$noindex = $noindex ?? true;
$brandHref = $user === null ? '/' : ($user['role'] === 'driver' ? '/surucu/lent' : '/musteri/elanlarim');
// Statik faylların dəyişmə vaxtına əsaslı keş-sındırma (bölmə 12.1 keş
// başlıqları ilə birlikdə işləyir) — hər yeniləmədə brauzer keşi avtomatik
// köhnəlir, əl ilə versiya nömrəsi artırmağa ehtiyac qalmır.
$docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$cssVer = @filemtime($docRoot . '/assets/css/app.css') ?: time();
$jsVer = @filemtime($docRoot . '/assets/js/app.js') ?: time();
$iconVer = @filemtime($docRoot . '/assets/icons/icon-192.png') ?: time();
// Rola görə fərqli "Ana ekrana əlavə et" adı (bölmə: qeydiyyatdan asılı PWA adı) —
// manifest həm android/chrome quraşdırma dialoqu, apple-mobile-web-app-title isə
// iOS Safari-nin "Ana ekrana əlavə et" sahəsini əvvəlcədən doldurur.
$homeScreenName = match ($user['role'] ?? null) {
    'driver' => 'Birlikdə Yük Daşıma',
    'customer' => 'Birlikdə Yük Müştəri',
    default => 'Birlikdə Yük',
};
$manifestFile = match ($user['role'] ?? null) {
    'driver' => 'manifest-driver.webmanifest',
    'customer' => 'manifest-customer.webmanifest',
    default => 'manifest.webmanifest',
};
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(t('home.subtitle')) ?>">
<?php if ($noindex): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<meta name="theme-color" content="#2F6FED">
<meta name="apple-mobile-web-app-title" content="<?= e($homeScreenName) ?>">
<link rel="manifest" href="/<?= $manifestFile ?>?v=<?= $iconVer ?>">
<link rel="icon" href="/assets/icons/icon-192.png?v=<?= $iconVer ?>">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png?v=<?= $iconVer ?>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= $cssVer ?>">
<meta property="og:site_name" content="Birlikdə Yük">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
</head>
<body data-role="<?= e($user['role'] ?? '') ?>" data-auth="<?= Auth::check() ? '1' : '0' ?>" data-driver-status="<?= e($user['driver_status'] ?? '') ?>">
<?php \App\Core\View::partial('partials/splash'); ?>
<?php \App\Core\View::partial('partials/bg_blobs'); ?>
<header class="top-bar">
  <a href="<?= e($brandHref) ?>" class="brand">Birlikdə <span class="amber">Yük</span></a>
  <?php if (!Auth::check()): ?>
  <nav class="lang-switch">
    <a href="?lang=az" class="<?= $lang === 'az' ? 'active' : '' ?>">AZ</a>
    <a href="?lang=ru" class="<?= $lang === 'ru' ? 'active' : '' ?>">RU</a>
    <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
  </nav>
  <?php endif; ?>
</header>
<main>
<?= $content ?>
</main>
<?php if (Auth::check()): ?>
<?php \App\Core\View::partial('partials/bottom_nav'); ?>
<?php endif; ?>
<?php \App\Core\View::partial('partials/install_prompt'); ?>
<?php \App\Core\View::partial('partials/photo_lightbox'); ?>
<script src="/assets/js/app.js?v=<?= $jsVer ?>" defer></script>
</body>
</html>
