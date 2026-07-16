<?php
/** @var string $content */
/** @var string|null $pageTitle */
use App\Core\Lang;
use App\Core\Auth;

$lang = Lang::current();
$title = isset($pageTitle) ? $pageTitle . ' · ' . t('app_name') : t('app_name') . ' — ' . t('home.title');
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(t('home.subtitle')) ?>">
<meta name="theme-color" content="#131315">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link rel="stylesheet" href="/assets/css/app.css">
<meta property="og:site_name" content="Birlikdə Yük">
</head>
<body>
<header class="top-bar">
  <a href="/" class="brand">Birlikdə <span class="amber">Yük</span></a>
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
<script src="/assets/js/app.js" defer></script>
</body>
</html>
