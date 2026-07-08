<div class="auth-card glass">
    <div class="brand">Birlikdə <span>Bots</span></div>
    <h1>Giriş</h1>
    <form method="post" action="/login">
        <?= \App\Core\Csrf::field() ?>
        <label>Email
            <input type="email" name="email" required autofocus placeholder="admin@birlikde.biz">
        </label>
        <label>Parol
            <input type="password" name="password" required placeholder="••••••••">
        </label>
        <button type="submit" class="btn btn-primary">Daxil ol</button>
    </form>
    <p class="auth-footer">Hesabınız yoxdur? <a href="/register">Qeydiyyatdan keçin</a></p>
</div>
