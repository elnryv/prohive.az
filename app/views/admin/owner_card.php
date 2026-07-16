<?php
/** @var array<string,mixed> $owner */
/** @var array<int,array<string,mixed>> $houses */
/** @var array<int,array<string,mixed>> $payments */
/** @var float $effectivePrice */
/** @var float $defaultPrice */
/** @var bool $isVisible */

$statusLabel = [
    'trial' => 'Sınaq', 'paid' => 'Ödənilib', 'free' => 'Pulsuz', 'expired' => 'Bitib', 'blocked' => 'Bloklanıb',
];
$waMessage = rawurlencode('Salam ' . $owner['full_name'] . ', Birlikdə Getdik platformasından yazırıq.');
?>
<section class="admin-owner-card">
    <p><a href="/owners">&larr; Ev sahiblərinə qayıt</a></p>
    <h1><?= View::e($owner['full_name']) ?></h1>
    <p class="admin-meta">
        <?= View::e($owner['phone']) ?> ·
        <span class="badge badge--status-<?= View::e($owner['billing_status']) ?>"><?= $statusLabel[$owner['billing_status']] ?? $owner['billing_status'] ?></span>
        · <?= $isVisible ? 'Saytda görünür' : 'Saytda gizlədilib' ?>
        · Qeydiyyat: <?= View::e(date('d.m.Y', strtotime($owner['created_at']))) ?>
    </p>

    <div class="admin-owner-actions">
        <?php if ($owner['billing_status'] !== 'free'): ?>
            <form method="post" action="/owners/<?= (int) $owner['id'] ?>/pulsuz" class="admin-inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Pulsuz et</button>
            </form>
        <?php endif; ?>

        <form method="post" action="/owners/<?= (int) $owner['id'] ?>/pullu" class="admin-inline-form admin-price-form">
            <?= Csrf::field() ?>
            <input type="number" step="0.01" min="0.01" name="custom_price" placeholder="<?= number_format($defaultPrice, 2) ?>" value="<?= $owner['custom_price'] !== null ? View::e((string) $owner['custom_price']) : '' ?>">
            <button type="submit" class="btn btn--primary">Pullu et / Qiyməti tətbiq et</button>
        </form>

        <?php if ($owner['custom_price'] !== null): ?>
            <form method="post" action="/owners/<?= (int) $owner['id'] ?>/qiymeti-sil" class="admin-inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn">Fərdi qiyməti sil</button>
            </form>
        <?php endif; ?>

        <?php if ($owner['billing_status'] === 'blocked'): ?>
            <form method="post" action="/owners/<?= (int) $owner['id'] ?>/aktivlesdir" class="admin-inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Aktiv et</button>
            </form>
        <?php else: ?>
            <form method="post" action="/owners/<?= (int) $owner['id'] ?>/blokla" class="admin-inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--danger">Blokla</button>
            </form>
        <?php endif; ?>

        <a class="btn" target="_blank" rel="noopener" href="https://wa.me/<?= View::e($owner['phone']) ?>?text=<?= $waMessage ?>">WhatsApp yaz</a>
    </div>

    <p class="admin-note">Effektiv qiymət: <strong><?= number_format($effectivePrice, 0) ?> AZN/ay</strong> (ümumi qiymət: <?= number_format($defaultPrice, 0) ?> AZN)</p>

    <h2>Evləri (<?= count($houses) ?>)</h2>
    <table class="admin-table">
        <thead><tr><th>Ev</th><th>Bölgə</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($houses as $h): ?>
                <tr>
                    <td><?= View::e($h['title']) ?></td>
                    <td><?= View::e($h['region_name_az']) ?></td>
                    <td><?= View::e($h['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Ödəniş tarixçəsi (<?= count($payments) ?>)</h2>
    <?php if ($payments === []): ?>
        <p class="admin-empty">Hələ ödəniş edilməyib.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>Məbləğ</th><th>Status</th><th>Tarix</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr><td><?= number_format((float) $p['amount'], 0) ?> AZN</td><td><?= View::e($p['status']) ?></td><td><?= View::e($p['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
