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
  <div class="card">
    <button type="button" id="profile-push-btn" class="btn btn-outline btn-block"
      data-msg-unsupported="<?= e(t('profile_push.unsupported')) ?>"
      data-msg-denied="<?= e(t('profile_push.denied')) ?>">
      🔔 <?= e(t('profile_push.btn')) ?>
    </button>
    <p id="profile-push-success" class="text-soft" style="color:var(--ok);margin-top:8px" hidden><?= e(t('profile_push.success')) ?></p>
    <p id="profile-push-error" class="text-soft" style="color:var(--danger);margin-top:8px" hidden></p>
  </div>
  <form method="post" action="/cixis">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-outline btn-block"><?= e(t('nav.logout')) ?></button>
  </form>
</div>
