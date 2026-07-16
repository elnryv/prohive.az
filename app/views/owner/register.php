<section class="auth-page">
    <h1><?= View::e(Lang::t('owner.register_title')) ?></h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $err): ?><li><?= View::e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/sahib/qeydiyyat" class="auth-form">
        <?= Csrf::field() ?>
        <label><?= View::e(Lang::t('owner.field_full_name')) ?>
            <input type="text" name="full_name" value="<?= View::e($old['full_name'] ?? '') ?>" required minlength="2">
        </label>
        <label><?= View::e(Lang::t('owner.field_phone')) ?>
            <input type="tel" name="phone" placeholder="050 123 45 67" value="<?= View::e($old['phone'] ?? '') ?>" required>
        </label>
        <label><?= View::e(Lang::t('owner.field_password')) ?>
            <input type="password" name="password" required minlength="6">
        </label>
        <label><?= View::e(Lang::t('owner.field_password2')) ?>
            <input type="password" name="password2" required minlength="6">
        </label>
        <label class="checkbox-field">
            <input type="checkbox" name="agree" required>
            <span><?= View::e(Lang::t('owner.field_agree')) ?></span>
        </label>
        <button type="submit" class="btn btn--primary btn--large"><?= View::e(Lang::t('owner.action_register')) ?></button>
    </form>

    <p class="auth-switch"><?= View::e(Lang::t('owner.already_have_account')) ?> <a href="/sahib/giris"><?= View::e(Lang::t('owner.go_to_login')) ?></a></p>
</section>
