<?php
/** @var int $todayVisitors */
/** @var int $activeSubscriptions */
/** @var float $mrr */
/** @var int $pendingCount */
/** @var int $todayWaClicks */
/** @var array<int,array<string,mixed>> $regionDemand */
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

    <p class="admin-note">Canlı lent (SSE) Faza 5-də aktivləşəcək — hazırda statistika səhifə yükləndikdə hesablanır.</p>

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
</section>
