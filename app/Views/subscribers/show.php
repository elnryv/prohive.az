<div class="page-header">
    <h2><?= e(trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? ''))) ?: 'Abunəçi' ?></h2>
</div>

<div class="panel glass">
    <p><strong>Chat ID:</strong> <?= (int) $subscriber['telegram_chat_id'] ?></p>
    <p><strong>Username:</strong> <?= $subscriber['username'] ? '@' . e($subscriber['username']) : '—' ?></p>
    <p><strong>Status:</strong> <span class="badge badge-<?= e($subscriber['status']) ?>"><?= e($subscriber['status']) ?></span></p>
    <p><strong>Hazırkı node:</strong> <?= $currentNode ? e($currentNode['node_key']) : '—' ?></p>
    <p><strong>Abunə tarixi:</strong> <?= e($subscriber['subscribed_at']) ?></p>
</div>

<div class="panel glass">
    <h3>Custom sahələr</h3>
    <?php if ($fields === []): ?>
        <p class="empty-state">Sahə yoxdur.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Açar</th><th>Dəyər</th></tr></thead>
            <tbody>
            <?php foreach ($fields as $f): ?>
                <tr><td><code><?= e($f['field_key']) ?></code></td><td><?= e($f['field_value']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="panel glass">
    <h3>Tag-lar</h3>
    <div class="tag-list">
        <?php foreach ($tags as $t): ?>
            <span class="tag-chip">
                <?= e($t['name']) ?>
                <form method="post" action="/bots/<?= (int) $bot['id'] ?>/subscribers/<?= (int) $subscriber['id'] ?>/tags/<?= (int) $t['id'] ?>/delete" style="display:inline">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn-link btn-link-danger" title="Sil">×</button>
                </form>
            </span>
        <?php endforeach; ?>
    </div>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/subscribers/<?= (int) $subscriber['id'] ?>/tags" class="inline-form">
        <?= \App\Core\Csrf::field() ?>
        <select name="tag_id">
            <option value="">— mövcud tag —</option>
            <?php foreach ($allTags as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="muted">və ya</span>
        <input type="text" name="tag_name" placeholder="yeni tag adı">
        <button type="submit" class="btn btn-sm">Əlavə et</button>
    </form>
</div>

<div class="panel glass">
    <h3>Mesaj tarixçəsi</h3>
    <?php if ($logs === []): ?>
        <p class="empty-state">Log yoxdur.</p>
    <?php else: ?>
        <div class="log-list">
            <?php foreach ($logs as $l): ?>
                <div class="log-item log-<?= e($l['direction']) ?>">
                    <span class="log-dir"><?= $l['direction'] === 'in' ? '⬅' : '➡' ?></span>
                    <span class="log-type"><?= e($l['message_type']) ?></span>
                    <span class="log-content"><?= e(mb_substr((string) $l['content'], 0, 200)) ?></span>
                    <span class="log-time muted"><?= e($l['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
