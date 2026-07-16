<?php
/** @var int $todayVisitors */
/** @var int $activeSubscriptions */
/** @var float $mrr */
/** @var int $pendingCount */
/** @var int $todayWaClicks */
/** @var array<int,array<string,mixed>> $regionDemand */
/** @var array<int,array<string,mixed>> $expiringOwners */
?>
<section class="admin-dashboard">
    <h1>Dashboard</h1>

    <div class="admin-cards">
        <div class="admin-card">
            <span class="admin-card__label">Bugünkü ziyarətçi</span>
            <span class="admin-card__value"><?= $todayVisitors ?></span>
        </div>
        <div class="admin-card">
            <span class="admin-card__label">Aktiv abunə</span>
            <span class="admin-card__value"><?= $activeSubscriptions ?></span>
        </div>
        <div class="admin-card">
            <span class="admin-card__label">MRR</span>
            <span class="admin-card__value"><?= number_format($mrr, 0) ?> AZN</span>
        </div>
        <div class="admin-card admin-card--warn">
            <span class="admin-card__label">Təsdiq gözləyən</span>
            <span class="admin-card__value"><a href="/tesdiq"><?= $pendingCount ?></a></span>
        </div>
        <div class="admin-card">
            <span class="admin-card__label">Bugünkü WhatsApp klik</span>
            <span class="admin-card__value"><?= $todayWaClicks ?></span>
        </div>
    </div>

    <h2>Canlı lent</h2>
    <ul id="admin-live-lent" class="admin-live-lent" data-sse-url="/sse">
        <li class="admin-live-lent__empty">Yeni hadisələr burada canlı görünəcək...</li>
    </ul>

    <h2>Bölgələr üzrə tələb (son 30 gün)</h2>
    <table class="admin-table">
        <thead><tr><th>Bölgə</th><th>Baxış</th><th>WhatsApp klik</th></tr></thead>
        <tbody>
            <?php foreach ($regionDemand as $r): ?>
                <tr>
                    <td><?= View::e($r['name_az']) ?></td>
                    <td><?= (int) $r['views'] ?></td>
                    <td><?= (int) $r['wa_clicks'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($expiringOwners !== []): ?>
    <h2>Bitməyə 5 gün qalanlar (xatırlatma göndər)</h2>
    <table class="admin-table">
        <thead><tr><th>Ad</th><th>Nömrə</th><th>Bitmə tarixi</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($expiringOwners as $o):
                $waMsg = rawurlencode('Salam ' . $o['full_name'] . ', Birlikdə Getdik abunəniz tezliklə bitir. Uzatmaq üçün panelinizə daxil olun.');
            ?>
                <tr>
                    <td><a href="/owners/<?= (int) $o['id'] ?>"><?= View::e($o['full_name']) ?></a></td>
                    <td><?= View::e($o['phone']) ?></td>
                    <td><?= View::e($o['expires_on']) ?></td>
                    <td><a class="btn" target="_blank" rel="noopener" href="https://wa.me/<?= View::e($o['phone']) ?>?text=<?= $waMsg ?>">Xatırlatma göndər</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>
