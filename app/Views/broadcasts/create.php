<div class="panel glass panel-narrow">
    <h2>Yeni broadcast</h2>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/broadcasts">
        <?= \App\Core\Csrf::field() ?>
        <label>Mesaj mətni <span class="muted">({{field_key}} dəyişənləri işlədilə bilər)</span>
            <textarea name="message_text" rows="5" required></textarea>
        </label>
        <label>Hədəf tag (boş = bütün aktiv abunəçilər)
            <select name="target_tag_id">
                <option value="">— hamısı —</option>
                <?php foreach ($tags as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Növbəyə qoy</button>
    </form>
</div>
