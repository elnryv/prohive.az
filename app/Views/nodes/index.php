<div class="page-header">
    <h2>Node-lar</h2>
    <a href="/bots/<?= (int) $bot['id'] ?>/nodes/create" class="btn btn-primary">+ Yeni node</a>
</div>

<?php if ($nodes === []): ?>
    <p class="empty-state">Hələ node yaradılmayıb.</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Key</th><th>Başlıq</th><th>Tip</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($nodes as $n): ?>
            <tr>
                <td><code><?= e($n['node_key']) ?></code></td>
                <td><?= e($n['title'] ?? '—') ?></td>
                <td><span class="badge"><?= e($n['node_type']) ?></span></td>
                <td>
                    <a href="/bots/<?= (int) $bot['id'] ?>/nodes/<?= (int) $n['id'] ?>/edit">Redaktə</a>
                    <form method="post" action="/bots/<?= (int) $bot['id'] ?>/nodes/<?= (int) $n['id'] ?>/delete" onsubmit="return confirm('Node silinsin?');" style="display:inline">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn-link btn-link-danger">Sil</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
