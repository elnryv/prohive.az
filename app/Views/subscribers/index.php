<div class="page-header">
    <h2>Abunəçilər</h2>
</div>

<form method="get" class="inline-form">
    <input type="text" name="q" placeholder="Ad və ya username axtar..." value="<?= e($search) ?>">
    <select name="tag_id">
        <option value="">Bütün tag-lar</option>
        <?php foreach ($tags as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= (string) $selectedTagId === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm">Axtar</button>
</form>

<?php if ($subscribers === []): ?>
    <p class="empty-state">Abunəçi tapılmadı.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Ad</th><th>Username</th><th>Status</th><th>Son görülmə</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($subscribers as $s): ?>
            <tr>
                <td><?= e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?: '—' ?></td>
                <td><?= $s['username'] ? '@' . e($s['username']) : '—' ?></td>
                <td><span class="badge badge-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
                <td><?= e($s['last_seen_at'] ?? '—') ?></td>
                <td><a href="/bots/<?= (int) $bot['id'] ?>/subscribers/<?= (int) $s['id'] ?>">Bax</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
