<section class="admin-settings">
    <h1>Parametrlər</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors"><?php foreach ($errors as $e): ?><li><?= View::e($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if ($saved): ?><p class="notice notice--success">Parametrlər yadda saxlanıldı.</p><?php endif; ?>

    <form method="post" action="/settings" class="admin-settings-form">
        <?= Csrf::field() ?>
        <label>Ümumi aylıq qiymət (AZN)
            <input type="number" step="0.01" min="0.01" name="default_monthly_price" value="<?= View::e($defaultPrice) ?>" required>
        </label>
        <label>Trial günü sayı
            <input type="number" min="1" name="trial_days" value="<?= View::e($trialDays) ?>" required>
        </label>
        <label>Sayt dəstək nömrəsi
            <input type="text" name="site_phone" value="<?= View::e($sitePhone) ?>" placeholder="0501234567">
        </label>
        <label class="checkbox-field">
            <input type="checkbox" name="maintenance_mode" <?= $maintenanceMode ? 'checked' : '' ?>>
            <span>Texniki fasilə rejimi</span>
        </label>
        <button type="submit" class="btn btn--primary btn--large">Yadda saxla</button>
    </form>
</section>
