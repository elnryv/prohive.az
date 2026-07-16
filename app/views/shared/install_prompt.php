<?php
/** @var string|null $installMode 'guest'|'owner' — layout tərəfindən təyin olunur */
$installMode = $installMode ?? 'guest';
$isOwnerMode = $installMode === 'owner';
?>
<div id="install-sheet" class="install-sheet" data-mode="<?= View::e($installMode) ?>">
    <div class="install-sheet__body">
        <div class="install-sheet__icon" aria-hidden="true"></div>
        <div class="install-sheet__text">
            <h3><?= View::e(Lang::t($isOwnerMode ? 'pwa.install_owner_title' : 'pwa.install_title')) ?></h3>
            <p id="install-sheet-body"><?= View::e(Lang::t($isOwnerMode ? 'pwa.install_owner_body' : 'pwa.install_body')) ?></p>
        </div>
    </div>
    <div class="install-sheet__actions">
        <button type="button" class="btn" id="install-dismiss"><?= View::e(Lang::t('pwa.install_dismiss')) ?></button>
        <button type="button" class="btn btn--primary" id="install-accept"><?= View::e(Lang::t('pwa.install_button')) ?></button>
    </div>
</div>
<template id="install-ios-body-text"><?= View::e(Lang::t('pwa.install_ios_body')) ?></template>
