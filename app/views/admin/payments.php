<?php
/** @var array<int,array<string,mixed>> $payments */
/** @var int $total */
/** @var string $status */
/** @var bool $paymentsEnabled */
?>
<section class="admin-payments">
    <h1>Ödənişlər (<?= $total ?>)</h1>

    <?php if (!$paymentsEnabled): ?>
        <p class="admin-note">Payriff açarı hələ doldurulmayıb — ödəniş axını Faza 4-də aktivləşəcək. Bu siyahı hazırda boşdur.</p>
    <?php endif; ?>

    <form method="get" action="/payments" class="admin-search-form">
        <select name="status">
            <option value="">Bütün statuslar</option>
            <?php foreach (['created', 'approved', 'declined', 'canceled', 'expired'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary">Filtrlə</button>
    </form>

    <?php if ($payments === []): ?>
        <p class="admin-empty">Ödəniş qeydi yoxdur.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>Sahib</th><th>Məbləğ</th><th>Status</th><th>Payriff ID</th><th>Tarix</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= View::e($p['owner_name']) ?> (<?= View::e($p['owner_phone']) ?>)</td>
                        <td><?= number_format((float) $p['amount'], 0) ?> AZN</td>
                        <td><?= View::e($p['status']) ?></td>
                        <td><?= View::e($p['payriff_order_id'] ?? '—') ?></td>
                        <td><?= View::e($p['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
