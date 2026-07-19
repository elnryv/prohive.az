<?php
/** @var string|null $error */
use App\Core\Csrf;
?>
<div class="admin-login">
  <div class="brand-mark brand-mark-lg"><?= icon('map-pin', 'icon', 30) ?></div>
  <h1 style="text-align:center">Birlikdə <span style="color:var(--primary)">Yük</span></h1>
  <p class="text-soft" style="text-align:center">İdarəetmə mərkəzi</p>
  <?php if ($error !== null): ?><div class="banner banner-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="/giris">
    <?= Csrf::field() ?>
    <div class="field">
      <label>İstifadəçi adı</label>
      <input type="text" name="username" required autofocus>
    </div>
    <div class="field">
      <label>Şifrə</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-amber btn-block">Giriş</button>
  </form>
</div>
