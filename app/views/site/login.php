<?php
/** @var string|null $error */
use App\Core\Csrf;
?>
<div class="container">
  <h1><?= e(t('auth.login_title')) ?></h1>

  <?php if ($error !== null): ?>
    <div class="banner banner-error"><?= e(t($error)) ?></div>
  <?php endif; ?>

  <form method="post" action="/giris" novalidate>
    <?= Csrf::field() ?>
    <div class="field">
      <label><?= e(t('common.phone')) ?></label>
      <input type="tel" name="phone" placeholder="<?= e(t('auth.phone_placeholder')) ?>" required autofocus>
    </div>
    <div class="field">
      <label><?= e(t('common.password')) ?></label>
      <input type="password" name="password" required>
    </div>
    <div class="field" style="display:flex;align-items:center;gap:8px">
      <input type="checkbox" name="remember" value="1" style="width:auto;min-height:auto">
      <label style="margin:0"><?= e(t('auth.remember_me')) ?></label>
    </div>
    <button type="submit" class="btn btn-amber btn-block"><?= e(t('nav.login')) ?></button>
  </form>

  <p class="text-soft" style="text-align:center;margin-top:16px">
    <?= e(t('auth.no_account')) ?> <a href="/qeydiyyat" style="color:var(--amber)"><?= e(t('nav.register')) ?></a>
  </p>
</div>
