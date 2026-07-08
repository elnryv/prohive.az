<div class="page-header">
    <h2>Açar sözlər</h2>
</div>

<?php if ($keywords === []): ?>
    <p class="empty-state">Açar söz yoxdur.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Açar söz</th><th>Uyğunluq tipi</th><th>Hədəf node</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($keywords as $k): ?>
            <tr>
                <td><?= e($k['keyword']) ?></td>
                <td><span class="badge"><?= e($k['match_type']) ?></span></td>
                <td><?= e($k['node_key']) ?></td>
                <td>
                    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/keywords/<?= (int) $k['id'] ?>/delete" onsubmit="return confirm('Silinsin?');">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn-link btn-link-danger">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="panel glass panel-narrow">
    <h3>Yeni açar söz</h3>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/keywords">
        <?= \App\Core\Csrf::field() ?>
        <label>Açar söz <input type="text" name="keyword" required></label>
        <label>Uyğunluq tipi
            <select name="match_type">
                <option value="contains">contains (daxilində)</option>
                <option value="exact">exact (tam bərabər)</option>
                <option value="starts">starts (bununla başlayır)</option>
            </select>
        </label>
        <label>Hədəf node
            <select name="target_node_id" required>
                <option value="">— seçin —</option>
                <?php foreach ($nodes as $n): ?>
                    <option value="<?= (int) $n['id'] ?>"><?= e($n['node_key']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Əlavə et</button>
    </form>
</div>
