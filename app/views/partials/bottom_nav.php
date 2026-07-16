<?php
use App\Core\Auth;

$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/';
$isDriver = Auth::isDriver();
$active = static fn (string $p) => $p === $path ? 'active' : '';
?>
<nav class="bottom-nav">
<?php if ($isDriver): ?>
  <a href="/surucu/lent" class="<?= $active('/surucu/lent') ?>">🚚<span><?= e(t('nav.feed')) ?></span></a>
  <a href="/surucu/tekliflerim" class="<?= $active('/surucu/tekliflerim') ?>">💬<span><?= e(t('nav.my_offers')) ?></span></a>
  <a href="/surucu/tarixce" class="<?= $active('/surucu/tarixce') ?>">🕓<span><?= e(t('nav.history')) ?></span></a>
  <a href="/surucu/profil" class="<?= $active('/surucu/profil') ?>">👤<span><?= e(t('nav.profile')) ?></span></a>
<?php else: ?>
  <a href="/musteri/elanlarim" class="<?= $active('/musteri/elanlarim') ?>">📦<span><?= e(t('nav.my_listings')) ?></span></a>
  <a href="/musteri/elan/yeni" class="fab"><?= $active('/musteri/elan/yeni') ?>+</a>
  <a href="/musteri/tarixce" class="<?= $active('/musteri/tarixce') ?>">🕓<span><?= e(t('nav.history')) ?></span></a>
  <a href="/musteri/profil" class="<?= $active('/musteri/profil') ?>">👤<span><?= e(t('nav.profile')) ?></span></a>
<?php endif; ?>
</nav>
