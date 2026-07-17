<?php
/** @var string $content */
/** @var string|null $pageTitle */
use App\Core\AdminAuth;

$title = isset($pageTitle) ? $pageTitle . ' · İdarəetmə mərkəzi' : 'İdarəetmə mərkəzi — Birlikdə Yük';
$admin = AdminAuth::admin();
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
$nav = [
    '/dashboard' => ['📊', 'Dashboard'],
    '/surucular' => ['🚚', 'Sürücülər'],
    '/musteriler' => ['👤', 'Müştərilər'],
    '/elanlar' => ['📦', 'Elanlar'],
    '/odenisler' => ['💳', 'Ödənişlər'],
    '/parametrler' => ['⚙️', 'Parametrlər'],
    '/kampaniya' => ['🔔', 'Push kampaniya'],
    '/loglar' => ['📜', 'Loglar'],
];
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
  <aside class="admin-side">
    <div class="brand" style="padding:16px 12px">Birlikdə <span class="amber">Yük</span><br><small class="text-soft">idarəetmə mərkəzi</small></div>
    <nav>
      <?php foreach ($nav as $href => [$icon, $label]): ?>
        <a href="<?= $href ?>" class="admin-nav-link <?= $path === $href ? 'active' : '' ?>"><?= $icon ?> <?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <form method="post" action="/cixis" style="padding:12px">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="btn btn-outline btn-block">Çıxış</button>
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
