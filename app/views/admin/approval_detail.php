<?php
/** @var array<string,mixed> $house */
/** @var array<int,array<string,mixed>> $photos */
/** @var array<int,array<string,mixed>> $amenities */
?>
<section class="admin-approval-detail">
    <p><a href="/tesdiq">&larr; Təsdiq növbəsinə qayıt</a></p>
    <h1><?= View::e($house['title']) ?></h1>
    <p class="admin-meta">
        Sahib: <?= View::e($house['owner_name']) ?> (<?= View::e($house['owner_phone']) ?>) ·
        Bölgə: <?= View::e($house['region_name_az']) ?><?= $house['village'] ? ' · ' . View::e($house['village']) : '' ?> ·
        Status: <strong><?= View::e($house['status']) ?></strong>
    </p>

    <?php if (isset($_GET['xeta']) && $_GET['xeta'] === 'sebeb'): ?>
        <p class="notice notice--warn">Geri göndərmək üçün səbəb yazılmalıdır.</p>
    <?php endif; ?>

    <?php if ($photos !== []): ?>
        <div class="admin-photo-grid admin-photo-grid--large">
            <?php foreach ($photos as $p): ?>
                <div class="admin-photo-tile">
                    <?php if ($p['is_video']): ?>
                        <video src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" controls muted></video>
                    <?php else: ?>
                        <img src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" alt="">
                    <?php endif; ?>
                    <?php if (!$p['is_approved']): ?><span class="badge badge--warn">Təsdiqlənməyib</span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <dl class="admin-detail-list">
        <dt>Otaq / Tutum</dt><dd><?= (int) $house['rooms'] ?> otaq · <?= (int) $house['capacity'] ?> nəfər</dd>
        <dt>Qiymət</dt><dd><?= number_format((float) $house['price_night'], 0) ?> AZN/gecə<?= $house['price_weekend'] ? ' · ' . number_format((float) $house['price_weekend'], 0) . ' AZN/həftəsonu' : '' ?></dd>
        <dt>WhatsApp</dt><dd><?= View::e($house['whatsapp_phone']) ?></dd>
        <dt>Təsvir</dt><dd><?= nl2br(View::e($house['description'])) ?></dd>
        <dt>Şərait</dt><dd><?php foreach ($amenities as $a): ?><span class="amenity-pill"><?= View::e($a['icon']) ?> <?= View::e($a['name_az']) ?></span> <?php endforeach; ?></dd>
        <?php if ($house['status'] === 'rejected' && $house['reject_reason']): ?>
            <dt>Əvvəlki rədd səbəbi</dt><dd><?= View::e($house['reject_reason']) ?></dd>
        <?php endif; ?>
    </dl>

    <?php if ($house['status'] === 'pending'): ?>
    <div class="admin-approval-actions">
        <form method="post" action="/tesdiq/<?= (int) $house['id'] ?>/tesdiqle">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--primary btn--large">Təsdiqlə</button>
        </form>
        <form method="post" action="/tesdiq/<?= (int) $house['id'] ?>/geri-gonder" class="admin-reject-form">
            <?= Csrf::field() ?>
            <textarea name="reason" placeholder="Geri göndərmə səbəbi (məcburi)" required></textarea>
            <button type="submit" class="btn btn--danger">Geri göndər</button>
        </form>
    </div>
    <?php endif; ?>
</section>
