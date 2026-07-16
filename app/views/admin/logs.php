<?php
/** @var array<int,array<string,mixed>> $logs */
/** @var int $total */
/** @var string[] $actions */
/** @var string $filterAction */
/** @var string $filterDate */
?>
<section class="admin-logs">
    <h1>Loglar (<?= $total ?>)</h1>

    <form method="get" action="/logs" class="admin-search-form">
        <select name="action">
            <option value="">Bütün əməliyyatlar</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?= View::e($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= View::e($a) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?= View::e($filterDate) ?>">
        <button type="submit" class="btn btn--primary">Filtrlə</button>
    </form>

    <table class="admin-table">
        <thead><tr><th>Tarix</th><th>Admin</th><th>Əməliyyat</th><th>Hədəf</th><th>Detallar</th><th>IP</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= View::e($l['created_at']) ?></td>
                    <td><?= View::e($l['username']) ?></td>
                    <td><?= View::e($l['action']) ?></td>
                    <td><?= $l['target_id'] !== null ? (int) $l['target_id'] : '—' ?></td>
                    <td><?= View::e($l['details'] ?? '') ?></td>
                    <td><?= View::e($l['ip'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
