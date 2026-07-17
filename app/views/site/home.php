<?php use App\Core\Auth; ?>
<div class="container">
  <section style="padding:32px 0 16px">
    <h1 style="font-size:28px"><?= e(t('home.title')) ?></h1>
    <p class="text-soft" style="font-size:16px"><?= e(t('home.subtitle')) ?></p>
  </section>

  <?php if (!Auth::check()): ?>
  <section class="role-cards" style="margin:24px 0">
    <a class="role-card" href="/qeydiyyat?rol=musteri">
      <div class="icon"><?= icon('box', 'icon', 32) ?></div>
      <h2><?= e(t('auth.role_customer')) ?></h2>
      <p class="text-soft"><?= e(t('auth.role_customer_desc')) ?></p>
    </a>
    <a class="role-card" href="/qeydiyyat?rol=surucu">
      <div class="icon"><?= icon('truck', 'icon', 32) ?></div>
      <h2><?= e(t('auth.role_driver')) ?></h2>
      <p class="text-soft"><?= e(t('auth.role_driver_desc')) ?></p>
    </a>
  </section>
  <p class="text-soft" style="text-align:center">
    <?= e(t('auth.has_account')) ?> <a href="/giris" class="link-amber"><?= e(t('nav.login')) ?></a>
  </p>
  <?php else: ?>
  <a class="btn btn-amber btn-block" href="<?= Auth::isDriver() ? '/surucu/lent' : '/musteri/elanlarim' ?>">
    <?= e(t(Auth::isDriver() ? 'nav.feed' : 'nav.my_listings')) ?>
  </a>
  <?php endif; ?>

  <section style="margin-top:40px">
    <h2><?= e(t('home.how_it_works')) ?></h2>
    <div class="card"><span class="chip chip-active">1</span> <?= e(t('home.step1')) ?></div>
    <div class="card"><span class="chip chip-active">2</span> <?= e(t('home.step2')) ?></div>
    <div class="card"><span class="chip chip-active">3</span> <?= e(t('home.step3')) ?></div>
  </section>
</div>
