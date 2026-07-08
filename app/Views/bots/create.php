<div class="panel glass panel-narrow">
    <h2>Yeni bot</h2>
    <p class="muted">Telegram tokeni <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>-dan alın.</p>
    <form method="post" action="/bots">
        <?= \App\Core\Csrf::field() ?>
        <label>Bot adı
            <input type="text" name="name" required autofocus placeholder="Məsələn: Dəstək botu">
        </label>
        <label>Telegram token
            <input type="text" name="telegram_token" required placeholder="123456789:AA...">
        </label>
        <button type="submit" class="btn btn-primary">Yarat</button>
    </form>
</div>
