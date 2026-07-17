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
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(t('home.subtitle')) ?>">
<?php if ($noindex): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<meta name="theme-color" content="#131315">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/app.css">
<meta property="og:site_name" content="Birlikdə Yük">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
</head>
<body data-role="<?= e($user['role'] ?? '') ?>" data-auth="<?= Auth::check() ? '1' : '0' ?>" data-driver-status="<?= e($user['driver_status'] ?? '') ?>">
<?php \App\Core\View::partial('partials/splash'); ?>
<header class="top-bar">
  <a href="<?= e($brandHref) ?>" class="brand">Birlikdə <span class="amber">Yük</span></a>
  <nav class="lang-switch">
    <a href="?lang=az" class="<?= $lang === 'az' ? 'active' : '' ?>">AZ</a>
    <a href="?lang=ru" class="<?= $lang === 'ru' ? 'active' : '' ?>">RU</a>
    <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
  </nav>
</header>
<main>
<?= $content ?>
</main>
<?php if (Auth::check()): ?>
<?php \App\Core\View::partial('partials/bottom_nav'); ?>
<?php endif; ?>
<?php \App\Core\View::partial('partials/install_prompt'); ?>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
