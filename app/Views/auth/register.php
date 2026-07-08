<div class="auth-card glass">
    <div class="brand">Birlikdə <span>Bots</span></div>
    <h1>Qeydiyyat</h1>
    <form method="post" action="/register">
        <?= \App\Core\Csrf::field() ?>
        <label>Ad Soyad
            <input type="text" name="name" required autofocus>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Parol
            <input type="password" name="password" required minlength="6">
        </label>
        <button type="submit" class="btn btn-primary">Qeydiyyatdan keç</button>
    </form>
    <p class="auth-footer">Artıq hesabınız var? <a href="/login">Daxil olun</a></p>
</div>
