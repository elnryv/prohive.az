<section class="admin-auth">
    <h1>Admin girişi</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $err): ?><li><?= View::e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/giris" class="admin-auth-form">
        <?= Csrf::field() ?>
        <label>İstifadəçi adı
            <input type="text" name="username" value="<?= View::e($oldUsername) ?>" required autofocus>
        </label>
        <label>Şifrə
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn--primary btn--large">Daxil ol</button>
    </form>
</section>
