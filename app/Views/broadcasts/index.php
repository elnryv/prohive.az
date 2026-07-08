<div class="page-header">
    <h2>Broadcast</h2>
    <a href="/bots/<?= (int) $bot['id'] ?>/broadcasts/create" class="btn btn-primary">+ Yeni broadcast</a>
</div>

<?php if ($broadcasts === []): ?>
    <p class="empty-state">Hələ broadcast yaradılmayıb.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Mətn</th><th>Status</th><th>Göndərilib / Cəmi</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($broadcasts as $b): ?>
            <tr>
                <td><?= e(mb_substr($b['message_text'], 0, 60)) ?></td>
                <td><span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
                <td><?= (int) $b['sent_count'] ?> / <?= (int) $b['total_count'] ?> <?php if ($b['failed_count'] > 0): ?><span class="muted">(<?= (int) $b['failed_count'] ?> xəta)</span><?php endif; ?></td>
                <td>
                    <?php if (!in_array($b['status'], ['sending', 'done'], true)): ?>
                    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/broadcasts/<?= (int) $b['id'] ?>/send">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn-link">Göndər</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
