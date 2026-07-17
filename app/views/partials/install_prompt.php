<div id="install-sheet" class="install-sheet" hidden>
  <div class="install-sheet-inner">
    <button type="button" id="install-close" class="install-close" aria-label="<?= e(t('common.close')) ?>">✕</button>

    <div id="install-android" hidden>
      <div class="install-icon">📲</div>
      <h2><?= e(t('install.title')) ?></h2>
      <p class="text-soft"><?= e(t('install.subtitle')) ?></p>
      <button type="button" id="install-android-btn" class="btn btn-amber btn-block"><?= e(t('install.android_cta')) ?></button>
    </div>

    <div id="install-ios" hidden>
      <div class="install-icon">📲</div>
      <h2><?= e(t('install.title')) ?></h2>
      <p class="text-soft"><?= e(t('install.ios_push_note')) ?></p>
      <ol class="install-steps">
        <li><span class="install-step-num">1</span> <?= e(t('install.ios_step1')) ?> <span class="install-share-icon">⬆️</span></li>
        <li><span class="install-step-num">2</span> <?= e(t('install.ios_step2')) ?></li>
        <li><span class="install-step-num">3</span> <?= e(t('install.ios_step3')) ?></li>
      </ol>
    </div>
  </div>
</div>

<div id="install-reminder" class="install-reminder" hidden>
  <span><?= e(t('install.reminder_text')) ?></span>
  <button type="button" id="install-reminder-btn" class="btn btn-sm btn-amber"><?= e(t('install.reminder_cta')) ?></button>
  <button type="button" id="install-reminder-close" class="install-close" aria-label="<?= e(t('common.close')) ?>">✕</button>
</div>

<div id="ios-push-banner" class="install-reminder" hidden>
  <span><?= e(t('install.ios_enable_push_text')) ?></span>
  <button type="button" id="ios-push-btn" class="btn btn-sm btn-amber"><?= e(t('install.ios_enable_push_cta')) ?></button>
  <button type="button" id="ios-push-close" class="install-close" aria-label="<?= e(t('common.close')) ?>">✕</button>
</div>
