<?php
/** @var array<string,mixed> $owner */
/** @var array<int,array<string,mixed>> $houses */
/** @var array<int,array<int,array<string,mixed>>> $stats */
/** @var bool $isVisible */
/** @var float $price */

$statusKey = [
    'trial' => 'owner.billing_status_trial',
    'paid' => 'owner.billing_status_paid',
    'free' => 'owner.billing_status_free',
    'expired' => 'owner.billing_status_expired',
    'blocked' => 'owner.billing_status_blocked',
][$owner['billing_status']] ?? 'owner.billing_status_expired';

$until = $owner['billing_status'] === 'paid' ? $owner['paid_until'] : $owner['trial_until'];

$houseStatusKey = [
    'draft' => 'owner.house_status_draft',
    'pending' => 'owner.house_status_pending',
    'approved' => 'owner.house_status_approved',
    'rejected' => 'owner.house_status_rejected',
];

$sparkline = static function (array $days, string $key): string {
    $values = array_map(static fn ($d) => (int) $d[$key], $days);
    $max = max(1, max($values));
    $w = 120;
    $h = 28;
    $step = count($values) > 1 ? $w / (count($values) - 1) : $w;
    $points = [];
    foreach ($values as $i => $v) {
        $x = round($i * $step, 1);
        $y = round($h - ($v / $max) * $h, 1);
        $points[] = "{$x},{$y}";
    }
    return implode(' ', $points);
};
?>
<section class="owner-dashboard">
    <h1><?= View::e(Lang::t('owner.dashboard_title')) ?></h1>

    <?php if (isset($_GET['gonderildi'])): ?>
        <p class="notice notice--success"><?= View::e(Lang::t('owner.submitted_notice')) ?></p>
    <?php endif; ?>

    <div class="billing-card">
        <div class="billing-card__status">
            <span class="badge badge--status-<?= View::e($owner['billing_status']) ?>"><?= View::e(Lang::t($statusKey)) ?></span>
            <?php if ($until): ?><span class="billing-card__until"><?= View::e(Lang::tf('owner.billing_until', date('d.m.Y', strtotime($until)))) ?></span><?php endif; ?>
        </div>
        <p class="billing-card__price"><?= View::e(Lang::tf('owner.billing_price', number_format($price, 0))) ?></p>
        <p class="billing-card__note"><?= $isVisible ? View::e(Lang::t('owner.billing_visible_note')) : View::e(Lang::t('owner.billing_hidden_note')) ?></p>
        <a class="btn btn--primary" href="/sahib/abune"><?= View::e(Lang::t('owner.billing_pay_button')) ?></a>
    </div>

    <?php if ($houses === []): ?>
        <div class="empty-state">
            <p><?= View::e(Lang::t('owner.dashboard_no_houses')) ?></p>
            <a class="btn btn--primary btn--large" href="/sahib/ev/yeni"><?= View::e(Lang::t('owner.dashboard_add_house')) ?></a>
        </div>
    <?php else: ?>
        <div class="owner-house-list">
            <?php foreach ($houses as $h): ?>
                <?php $days = $stats[(int) $h['id']] ?? []; ?>
                <div class="owner-house-row">
                    <div class="owner-house-row__photo">
                        <?php if ($h['cover_photo']): ?>
                            <img src="/uploads/houses/<?= (int) $h['id'] ?>/<?= View::e($h['cover_photo']) ?>" alt="">
                        <?php else: ?>
                            <div class="house-card__placeholder">🏡</div>
                        <?php endif; ?>
                    </div>
                    <div class="owner-house-row__body">
                        <h3><?= View::e($h['title']) ?></h3>
                        <p class="owner-house-row__meta">
                            <?= View::e($h['region_name_az']) ?><?= $h['village'] ? ' · ' . View::e($h['village']) : '' ?>
                            · <?= (int) $h['photo_count'] ?> foto
                        </p>
                        <span class="badge badge--house-<?= View::e($h['status']) ?>"><?= View::e(Lang::t($houseStatusKey[$h['status']] ?? 'owner.house_status_draft')) ?></span>
                        <?php if ($h['status'] === 'rejected' && $h['reject_reason']): ?>
                            <p class="owner-house-row__reject"><?= View::e(Lang::tf('owner.house_reject_reason', $h['reject_reason'])) ?></p>
                        <?php endif; ?>

                        <?php if ($days !== []): ?>
                        <div class="stats-mini">
                            <svg viewBox="0 0 120 28" class="sparkline" preserveAspectRatio="none">
                                <polyline points="<?= $sparkline($days, 'views') ?>" fill="none" stroke="#3E7C4F" stroke-width="2"></polyline>
                            </svg>
                            <span><?= (int) $h['views_total'] ?> <?= View::e(Lang::t('owner.stats_total_views')) ?></span>
                            <span><?= (int) $h['wa_clicks_total'] ?> <?= View::e(Lang::t('owner.stats_total_wa_clicks')) ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="owner-house-row__actions">
                            <a class="btn" href="/sahib/ev/<?= (int) $h['id'] ?>/redakte?addim=1"><?= View::e(Lang::t('owner.action_edit')) ?></a>
                            <?php if ($h['status'] === 'approved'): ?>
                                <a class="btn" href="/ev/<?= View::e($h['slug']) ?>" target="_blank" rel="noopener"><?= View::e(Lang::t('owner.action_view')) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
