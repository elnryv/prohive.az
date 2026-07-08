<div class="page-header">
    <h2>Sequence-lər</h2>
</div>

<?php if ($sequences === []): ?>
    <p class="empty-state">Hələ sequence yaradılmayıb.</p>
<?php else: ?>
    <?php foreach ($sequences as $seq): ?>
        <div class="panel glass">
            <h3><?= e($seq['name']) ?> <span class="badge badge-<?= e($seq['status']) ?>"><?= e($seq['status']) ?></span></h3>
            <p class="muted">Tetiklənmə tag: <?= e($seq['trigger_tag_id'] ? '#' . $seq['trigger_tag_id'] : '— seçilməyib —') ?></p>

            <table class="table">
                <thead><tr><th>Sıra</th><th>Gecikmə (saat)</th><th>Mətn</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($seq['messages'] as $m): ?>
                    <tr>
                        <td><?= (int) $m['sort_order'] ?></td>
                        <td><?= (int) $m['delay_hours'] ?></td>
                        <td><?= e(mb_substr($m['message_text'], 0, 80)) ?></td>
                        <td>
                            <form method="post" action="/bots/<?= (int) $bot['id'] ?>/sequences/<?= (int) $seq['id'] ?>/messages/<?= (int) $m['id'] ?>/delete" onsubmit="return confirm('Silinsin?');">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn-link btn-link-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="/bots/<?= (int) $bot['id'] ?>/sequences/<?= (int) $seq['id'] ?>/messages" class="inline-form">
                <?= \App\Core\Csrf::field() ?>
                <label>Gecikmə (saat) <input type="number" name="delay_hours" value="24" min="0"></label>
                <label>Mətn <input type="text" name="message_text" required></label>
                <label>Sıra <input type="number" name="sort_order" value="0"></label>
                <button type="submit" class="btn btn-sm">Mesaj əlavə et</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="panel glass panel-narrow">
    <h3>Yeni sequence</h3>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/sequences">
        <?= \App\Core\Csrf::field() ?>
        <label>Ad <input type="text" name="name" required></label>
        <label>Tetiklənmə tag-ı
            <select name="trigger_tag_id">
                <option value="">— seçilməyib —</option>
                <?php foreach ($tags as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Yarat</button>
    </form>
</div>
