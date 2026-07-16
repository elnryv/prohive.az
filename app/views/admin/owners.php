<?php
/** @var string $query */
/** @var array<int,array<string,mixed>> $owners */
/** @var int $total */
$statusLabel = [
    'trial' => 'Sınaq', 'paid' => 'Ödənilib', 'free' => 'Pulsuz', 'expired' => 'Bitib', 'blocked' => 'Bloklanıb',
];
?>
<section class="admin-owners">
    <h1>Ev sahibləri (<?= $total ?>)</h1>

    <form method="get" action="/owners" class="admin-search-form">
        <input type="text" name="q" value="<?= View::e($query) ?>" placeholder="Telefon (hissəvi), ad və ya ev adı...">
        <button type="submit" class="btn btn--primary">Axtar</button>
    </form>

    <table class="admin-table">
        <thead><tr><th>Ad</th><th>Nömrə</th><th>Ev sayı</th><th>Status</th><th>Qiymət</th><th>Bitmə tarixi</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($owners as $o): ?>
                <tr>
                    <td><?= View::e($o['full_name']) ?></td>
                    <td><?= View::e($o['phone']) ?></td>
                    <td><?= (int) $o['house_count'] ?></td>
                    <td><span class="badge badge--status-<?= View::e($o['billing_status']) ?>"><?= $statusLabel[$o['billing_status']] ?? $o['billing_status'] ?></span></td>
                    <td><?= $o['custom_price'] !== null ? number_format((float) $o['custom_price'], 0) . ' AZN (fərdi)' : '—' ?></td>
                    <td><?= View::e($o['billing_status'] === 'paid' ? ($o['paid_until'] ?? '—') : ($o['trial_until'] ?? '—')) ?></td>
                    <td><a class="btn" href="/owners/<?= (int) $o['id'] ?>">Kart</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
