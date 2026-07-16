<?php
/** @var array<int,array<string,mixed>> $houses */
/** @var array<int,array<string,mixed>> $photos */
?>
<section class="admin-approvals">
    <h1>Təsdiq növbəsi</h1>

    <?php if (isset($_GET['tesdiqlendi'])): ?><p class="notice notice--success">Ev təsdiqləndi və saytda dərc olundu.</p><?php endif; ?>
    <?php if (isset($_GET['geri_gonderildi'])): ?><p class="notice notice--warn">Ev geri göndərildi.</p><?php endif; ?>
    <?php if (isset($_GET['foto_tesdiqlendi'])): ?><p class="notice notice--success">Foto təsdiqləndi.</p><?php endif; ?>
    <?php if (isset($_GET['foto_rededildi'])): ?><p class="notice notice--warn">Foto rədd edildi.</p><?php endif; ?>

    <h2>Pending evlər (<?= count($houses) ?>)</h2>
    <?php if ($houses === []): ?>
        <p class="admin-empty">Təsdiq gözləyən ev yoxdur.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>Ev</th><th>Sahibi</th><th>Bölgə</th><th>Tarix</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($houses as $h): ?>
                    <tr>
                        <td><?= View::e($h['title']) ?></td>
                        <td><?= View::e($h['owner_name']) ?> (<?= View::e($h['owner_phone']) ?>)</td>
                        <td><?= View::e($h['region_name_az']) ?></td>
                        <td><?= View::e(date('d.m.Y', strtotime($h['created_at']))) ?></td>
                        <td><a class="btn" href="/tesdiq/<?= (int) $h['id'] ?>">Baxış</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Yeni fotolar (təsdiqlənmiş evlərə əlavə olunub, <?= count($photos) ?>)</h2>
    <?php if ($photos === []): ?>
        <p class="admin-empty">Gözləyən yeni foto yoxdur.</p>
    <?php else: ?>
        <div class="admin-photo-grid">
            <?php foreach ($photos as $p): ?>
                <div class="admin-photo-tile">
                    <?php if ($p['is_video']): ?>
                        <video src="/uploads/houses/<?= (int) $p['house_id'] ?>/<?= View::e($p['filename']) ?>" muted></video>
                    <?php else: ?>
                        <img src="/uploads/houses/<?= (int) $p['house_id'] ?>/<?= View::e($p['filename']) ?>" alt="">
                    <?php endif; ?>
                    <p><?= View::e($p['house_title']) ?></p>
                    <form method="post" action="/tesdiq/foto/<?= (int) $p['id'] ?>/tesdiqle" class="admin-inline-form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--primary">Təsdiqlə</button>
                    </form>
                    <form method="post" action="/tesdiq/foto/<?= (int) $p['id'] ?>/redd" class="admin-inline-form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--danger">Rədd et</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
