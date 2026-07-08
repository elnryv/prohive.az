<div class="page-header">
    <h2>Tag-lar</h2>
</div>

<?php if ($tags === []): ?>
    <p class="empty-state">Hələ tag yaradılmayıb.</p>
<?php else: ?>
    <div class="tag-list">
        <?php foreach ($tags as $t): ?>
            <span class="tag-chip">
                <?= e($t['name']) ?>
                <form method="post" action="/bots/<?= (int) $bot['id'] ?>/tags/<?= (int) $t['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Tag silinsin?');">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn-link btn-link-danger" title="Sil">×</button>
                </form>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel glass panel-narrow">
    <h3>Yeni tag</h3>
    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/tags">
        <?= \App\Core\Csrf::field() ?>
        <label>Tag adı <input type="text" name="name" required></label>
        <button type="submit" class="btn btn-primary">Əlavə et</button>
    </form>
</div>
