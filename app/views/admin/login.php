<?php
/** @var string|null $error */
use App\Core\Csrf;
?>
<div class="admin-login">
  <h1>Birlikdə <span style="color:var(--amber)">Yük</span></h1>
  <p class="text-soft">İdarəetmə mərkəzi</p>
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
