<div class="page-header">
    <h2>Botlarım</h2>
    <a href="/bots/create" class="btn btn-primary">+ Yeni bot</a>
</div>

<?php if ($bots === []): ?>
    <p class="empty-state">Hələ bot yaratmamısınız.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($bots as $bot): ?>
            <div class="card glass">
                <h3><?= e($bot['name']) ?></h3>
                <p class="muted">@<?= e($bot['telegram_username'] ?? '—') ?></p>
                <span class="badge badge-<?= e($bot['status']) ?>"><?= e($bot['status']) ?></span>
                <div class="card-actions">
                    <a href="/bots/<?= (int) $bot['id'] ?>/nodes" class="btn btn-sm">Node-lar</a>
                    <a href="/bots/<?= (int) $bot['id'] ?>/edit" class="btn btn-sm btn-ghost">Ayarlar</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
