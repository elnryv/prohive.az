<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Phone;

$user = Auth::user();
$profileAction = $user['role'] === 'driver' ? '/surucu/profil' : '/musteri/profil';
$xetaCodes = $_GET['xeta'] ?? '';
$xetaList = $xetaCodes === '' ? [] : explode(',', $xetaCodes);
?>
<div class="container">
  <h1><?= e(t('nav.profile')) ?></h1>

  <?php if (($_GET['netice'] ?? '') === 'ok'): ?>
    <div class="banner" style="border-left-color:var(--ok)"><?= e(t('profile_edit.saved')) ?></div>
  <?php endif; ?>
  <?php foreach ($xetaList as $code): ?>
    <div class="banner banner-error">
      <?= e(t(match ($code) {
          'nomre_yanlis' => 'profile_edit.phone_invalid',
          'nomre_movcuddur' => 'profile_edit.phone_taken',
          default => 'profile_edit.photo_error',
      })) ?>
    </div>
  <?php endforeach; ?>

  <div class="card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <?php if (!empty($user['profile_photo'])): ?>
        <img src="/uploads/profiles/<?= e($user['profile_photo']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:50%">
      <?php else: ?>
        <div style="width:64px;height:64px;border-radius:50%;background:var(--card-hi);display:flex;align-items:center;justify-content:center;color:var(--txt-soft)"><?= icon('user', 'icon', 28) ?></div>
      <?php endif; ?>
      <div>
        <p><strong><?= e($user['full_name']) ?></strong></p>
        <p class="text-soft"><?= e(Phone::display($user['phone'])) ?></p>
      </div>
    </div>

    <form method="post" action="<?= e($profileAction) ?>" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <div class="field">
        <label for="profile_photo"><?= e(t('profile_edit.photo_label')) ?></label>
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
      </div>
      <div class="field">
        <label for="profile_phone"><?= e(t('profile_edit.phone_label')) ?></label>
        <input type="tel" id="profile_phone" name="phone" value="<?= e($user['phone']) ?>">
      </div>
      <button type="submit" class="btn btn-amber btn-block"><?= e(t('profile_edit.save')) ?></button>
    </form>
  </div>

  <div class="card">
    <button type="button" id="profile-push-btn" class="btn btn-outline btn-block"
      data-msg-unsupported="<?= e(t('profile_push.unsupported')) ?>"
      data-msg-denied="<?= e(t('profile_push.denied')) ?>"
      style="display:flex;align-items:center;justify-content:center;gap:8px">
      <?= icon('bell') ?> <?= e(t('profile_push.btn')) ?>
    </button>
    <p id="profile-push-success" class="text-soft" style="color:var(--ok);margin-top:8px" hidden><?= e(t('profile_push.success')) ?></p>
    <p id="profile-push-error" class="text-soft" style="color:var(--danger);margin-top:8px" hidden></p>
  </div>
  <form method="post" action="/cixis">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-outline btn-block" style="display:flex;align-items:center;justify-content:center;gap:8px"><?= icon('logout') ?> <?= e(t('nav.logout')) ?></button>
  </form>
</div>
