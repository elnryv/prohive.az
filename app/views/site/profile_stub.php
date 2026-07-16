<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Phone;

$user = Auth::user();
?>
<div class="container">
  <h1><?= e(t('nav.profile')) ?></h1>
  <div class="card">
    <p><strong><?= e($user['full_name']) ?></strong></p>
    <p class="text-soft"><?= e(Phone::display($user['phone'])) ?></p>
  </div>
  <form method="post" action="/cixis">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-outline btn-block"><?= e(t('nav.logout')) ?></button>
  </form>
</div>
