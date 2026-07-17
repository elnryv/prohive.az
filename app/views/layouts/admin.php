<?php
/** @var string $content */
/** @var string|null $pageTitle */
use App\Core\AdminAuth;
use App\Core\Csrf;

$title = isset($pageTitle) ? $pageTitle . ' · İdarəetmə mərkəzi' : 'İdarəetmə mərkəzi — Birlikdə Yük';
$admin = AdminAuth::admin();
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';

// İlk 4-ü alt naviqasiyada sabit görünür, qalanı "Daha" menyusuna düşür.
$primaryNav = [
    '/dashboard' => ['home', 'Dashboard'],
    '/surucular' => ['truck', 'Sürücülər'],
    '/elanlar' => ['box', 'Elanlar'],
    '/odenisler' => ['wallet', 'Ödənişlər'],
];
$moreNav = [
    '/musteriler' => ['user', 'Müştərilər'],
    '/parametrler' => ['settings', 'Parametrlər'],
    '/kampaniya' => ['bell', 'Push kampaniya'],
    '/loglar' => ['list', 'Loglar'],
];
$isMoreActive = array_key_exists($path, $moreNav);
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= e($title) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body data-auth="0">
<?php if ($admin !== null): ?>
<div class="admin-shell">
  <header class="admin-topbar">
    <span class="brand">Birlikdə <span class="amber">Yük</span></span>
    <span class="text-soft" style="font-size:12px">idarəetmə mərkəzi</span>
  </header>

  <main class="admin-main">
    <?= $content ?>
  </main>

  <nav class="bottom-nav admin-bottom-nav">
    <?php foreach ($primaryNav as $href => [$iconName, $label]): ?>
      <a href="<?= $href ?>" class="<?= $path === $href ? 'active' : '' ?>"><?= icon($iconName) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
    <details class="admin-more">
      <summary class="<?= $isMoreActive ? 'active' : '' ?>"><span class="admin-more-inner"><?= icon('dots') ?><span>Daha</span></span></summary>
      <div class="admin-more-sheet">
        <?php foreach ($moreNav as $href => [$iconName, $label]): ?>
          <a href="<?= $href ?>" class="<?= $path === $href ? 'active' : '' ?>"><?= icon($iconName) ?> <?= e($label) ?></a>
        <?php endforeach; ?>
        <form method="post" action="/cixis">
          <?= Csrf::field() ?>
          <button type="submit"><?= icon('logout') ?> Çıxış</button>
        </form>
      </div>
    </details>
  </nav>
</div>
<?php else: ?>
  <?= $content ?>
<?php endif; ?>
</body>
</html>
