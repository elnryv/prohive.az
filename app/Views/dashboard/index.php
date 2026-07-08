<div class="stat-grid">
    <div class="stat-card glass">
        <div class="stat-value"><?= (int) $botCount ?></div>
        <div class="stat-label">Bot sayı</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-value"><?= (int) $subscriberCount ?></div>
        <div class="stat-label">Ümumi abunəçi</div>
    </div>
</div>

<div class="panel glass">
    <h2>Son 7 gün — mesaj statistikası</h2>
    <?php $max = max(array_column($chart, 'total')) ?: 1; ?>
    <div class="bar-chart">
        <?php foreach ($chart as $point): ?>
            <div class="bar-col">
                <div class="bar" style="height: <?= (int) round(($point['total'] / $max) * 100) ?>%;" title="<?= (int) $point['total'] ?>"></div>
                <div class="bar-label"><?= e(date('d.m', strtotime($point['day']))) ?></div>
                <div class="bar-count"><?= (int) $point['total'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel glass">
    <h2>Son botlar</h2>
    <?php if ($bots === []): ?>
        <p class="empty-state">Hələ bot yaratmamısınız. <a href="/bots/create">İlk botunuzu yaradın</a>.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Ad</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($bots as $b): ?>
                <tr>
                    <td><?= e($b['name']) ?></td>
                    <td><span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
                    <td><a href="/bots/<?= (int) $b['id'] ?>/nodes">Aç</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
