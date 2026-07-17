<?php
/** @var string $content */
/** @var string $pageTitle */
/** @var array $listing */
use App\Core\Config;
use App\Core\Lang;

$lang = Lang::current();
$title = e($pageTitle) . ' · ' . e(t('app_name'));
$description = mb_substr((string) $listing['description'], 0, 150);
$baseUrl = rtrim((string) Config::get('app.base_url'), '/');
$ogImage = $baseUrl . '/storage/og/' . $listing['public_code'] . '.png';
$canonical = $baseUrl . '/e/' . $listing['public_code'];
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $title ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#2F6FED">
<link rel="stylesheet" href="/assets/css/app.css">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Birlikdə Yük">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta name="twitter:card" content="summary_large_image">
</head>
<body>
<header class="top-bar">
  <a href="/" class="brand"><img class="brand-logo" src="/assets/icons/icon-192.png" alt="">Birlikdə <span class="amber">Yük</span></a>
</header>
<main><?= $content ?></main>
</body>
</html>
