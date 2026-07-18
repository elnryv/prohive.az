<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Phone;
use App\Core\View;

$user = Auth::user();
$profileAction = $user['role'] === 'driver' ? '/surucu/profil' : '/musteri/profil';
$xetaCodes = $_GET['xeta'] ?? '';
$xetaList = $xetaCodes === '' ? [] : explode(',', $xetaCodes);
$phoneParts = Phone::splitForInput($user['phone']);
$currentLang = Lang::current();
$langNames = ['az' => 'Azərbaycan', 'ru' => 'Русский', 'en' => 'English'];
?>
<div class="container">
  <?php if (($_GET['netice'] ?? '') === 'ok'): ?>
    <div class="banner" style="border-left-color:var(--ok)"><?= e(t('profile_edit.saved')) ?></div>
  <?php endif; ?>
  <?php foreach ($xetaList as $code): ?>
    <div class="banner banner-error">
      <?= e(t(match ($code) {
          'nomre_yanlis' => 'profile_edit.phone_invalid',
          'nomre_movcuddur' => 'profile_edit.phone_taken',
          'ad_bos' => 'profile_edit.name_required',
          default => 'profile_edit.photo_error',
      })) ?>
    </div>
  <?php endforeach; ?>

  <div class="profile-header">
    <form id="profile-photo-form" method="post" action="<?= e($profileAction) ?>" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <label class="profile-avatar-wrap" for="profile_photo">
        <?php if (!empty($user['profile_photo'])): ?>
          <img class="profile-avatar" src="/uploads/profiles/<?= e($user['profile_photo']) ?>" alt="">
        <?php else: ?>
          <div class="profile-avatar-fallback"><?= icon('user', 'icon', 28) ?></div>
        <?php endif; ?>
        <span class="profile-avatar-edit"><?= icon('camera', 'icon', 12) ?></span>
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" onchange="this.form.submit()">
      </label>
    </form>
    <div>
      <strong><?= e($user['full_name']) ?></strong>
      <span class="text-soft"><?= e(Phone::display($user['phone'])) ?></span>
    </div>
  </div>

  <div class="profile-menu">
    <details>
      <summary class="profile-row">
        <span class="profile-row-icon"><?= icon('edit', 'icon', 18) ?></span>
        <span><?= e(t('profile_menu.account')) ?></span>
        <span class="profile-row-chevron"><?= icon('chevron-right', 'icon', 18) ?></span>
      </summary>
      <div class="profile-row-body">
        <form method="post" action="<?= e($profileAction) ?>">
          <?= Csrf::field() ?>
          <div class="field">
            <label for="profile_full_name"><?= e(t('profile_edit.full_name_label')) ?></label>
            <input type="text" id="profile_full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
          </div>
          <?php View::partial('partials/phone_input', ['oldPrefix' => $phoneParts['prefix'], 'oldNumber' => $phoneParts['number']]); ?>
          <button type="submit" class="btn btn-amber btn-block"><?= e(t('profile_edit.save')) ?></button>
        </form>
      </div>
    </details>

    <details>
      <summary class="profile-row">
        <span class="profile-row-icon"><?= icon('globe', 'icon', 18) ?></span>
        <span><?= e(t('profile_menu.language')) ?></span>
        <span class="profile-row-meta"><?= e(strtoupper($currentLang)) ?></span>
        <span class="profile-row-chevron"><?= icon('chevron-right', 'icon', 18) ?></span>
      </summary>
      <div class="profile-row-body">
        <div class="lang-switch-inline">
          <?php foreach ($langNames as $code => $label): ?>
            <a href="?lang=<?= e($code) ?>" class="<?= $currentLang === $code ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </details>

    <button type="button" id="profile-push-btn" class="profile-row"
      data-msg-unsupported="<?= e(t('profile_push.unsupported')) ?>"
      data-msg-denied="<?= e(t('profile_push.denied')) ?>">
      <span class="profile-row-icon"><?= icon('bell', 'icon', 18) ?></span>
      <span><?= e(t('profile_menu.notifications')) ?></span>
      <span class="profile-row-chevron"><?= icon('chevron-right', 'icon', 18) ?></span>
    </button>
    <div class="profile-row-body" style="padding-top:0">
      <p id="profile-push-success" class="text-soft" style="color:var(--ok)" hidden><?= e(t('profile_push.success')) ?></p>
      <p id="profile-push-error" class="text-soft" style="color:var(--danger)" hidden></p>
    </div>

    <a class="profile-row" href="mailto:support@birlikde.biz">
      <span class="profile-row-icon"><?= icon('message', 'icon', 18) ?></span>
      <span><?= e(t('profile_menu.support')) ?></span>
      <span class="profile-row-chevron"><?= icon('chevron-right', 'icon', 18) ?></span>
    </a>

    <details>
      <summary class="profile-row">
        <span class="profile-row-icon"><?= icon('info', 'icon', 18) ?></span>
        <span><?= e(t('profile_menu.about')) ?></span>
        <span class="profile-row-chevron"><?= icon('chevron-right', 'icon', 18) ?></span>
      </summary>
      <div class="profile-row-body">
        <p class="text-soft"><?= e(t('profile_menu.about_text')) ?></p>
      </div>
    </details>

    <form method="post" action="/cixis">
      <?= Csrf::field() ?>
      <button type="submit" class="profile-row profile-row-danger">
        <span class="profile-row-icon"><?= icon('logout', 'icon', 18) ?></span>
        <span><?= e(t('nav.logout')) ?></span>
      </button>
    </form>
  </div>
</div>
