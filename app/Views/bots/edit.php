<div class="panel glass panel-narrow">
    <h2>Bot ayarları</h2>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>">
        <?= \App\Core\Csrf::field() ?>
        <label>Bot adı
            <input type="text" name="name" required value="<?= e($bot['name']) ?>">
        </label>
        <label>Status
            <select name="status">
                <option value="active" <?= $bot['status'] === 'active' ? 'selected' : '' ?>>Aktiv</option>
                <option value="paused" <?= $bot['status'] === 'paused' ? 'selected' : '' ?>>Dayandırılıb</option>
            </select>
        </label>
        <label>Başlanğıc node
            <select name="start_node_id">
                <option value="">— seçilməyib —</option>
                <?php foreach ($nodes as $n): ?>
                    <option value="<?= (int) $n['id'] ?>" <?= (int) $bot['start_node_id'] === (int) $n['id'] ? 'selected' : '' ?>>
                        <?= e($n['node_key']) ?><?= $n['title'] ? ' — ' . e($n['title']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Yadda saxla</button>
    </form>

    <div class="danger-zone">
        <h3>Təhlükəli zona</h3>
        <form method="post" action="/bots/<?= (int) $bot['id'] ?>/delete" onsubmit="return confirm('Botu silmək istədiyinizə əminsiniz?');">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-danger">Botu sil</button>
        </form>
    </div>
</div>
