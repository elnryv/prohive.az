<section class="auth-page">
    <h1><?= View::e(Lang::t('owner.login_title')) ?></h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $err): ?><li><?= View::e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/sahib/giris" class="auth-form">
        <?= Csrf::field() ?>
        <label><?= View::e(Lang::t('owner.field_phone')) ?>
            <input type="tel" name="phone" placeholder="050 123 45 67" value="<?= View::e($old['phone'] ?? '') ?>" required autofocus>
        </label>
        <label><?= View::e(Lang::t('owner.field_password')) ?>
            <input type="password" name="password" required>
        </label>
        <label class="checkbox-field">
            <input type="checkbox" name="remember" checked>
            <span><?= View::e(Lang::t('owner.field_remember')) ?></span>
        </label>
        <button type="submit" class="btn btn--primary btn--large"><?= View::e(Lang::t('owner.action_login')) ?></button>
    </form>

    <p class="auth-switch"><?= View::e(Lang::t('owner.no_account_yet')) ?> <a href="/sahib/qeydiyyat"><?= View::e(Lang::t('owner.go_to_register')) ?></a></p>
</section>
