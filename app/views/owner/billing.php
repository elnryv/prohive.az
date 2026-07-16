<?php
/** @var array<string,mixed> $owner */
/** @var bool $isVisible */
/** @var float $price */
/** @var bool $paymentsEnabled */

$statusKey = [
    'trial' => 'owner.billing_status_trial',
    'paid' => 'owner.billing_status_paid',
    'free' => 'owner.billing_status_free',
    'expired' => 'owner.billing_status_expired',
    'blocked' => 'owner.billing_status_blocked',
][$owner['billing_status']] ?? 'owner.billing_status_expired';

$until = $owner['billing_status'] === 'paid' ? $owner['paid_until'] : $owner['trial_until'];
?>
<section class="owner-billing">
    <h1><?= View::e(Lang::t('owner.billing_title')) ?></h1>

    <div class="billing-card billing-card--large">
        <span class="badge badge--status-<?= View::e($owner['billing_status']) ?>"><?= View::e(Lang::t($statusKey)) ?></span>
        <?php if ($until): ?><p class="billing-card__until"><?= View::e(Lang::tf('owner.billing_until', date('d.m.Y', strtotime($until)))) ?></p><?php endif; ?>
        <p class="billing-card__price"><?= View::e(Lang::tf('owner.billing_price', number_format($price, 0))) ?></p>

        <?php if ($owner['billing_status'] === 'free'): ?>
            <p class="notice notice--success"><?= View::e(Lang::t('owner.billing_active_free')) ?></p>
        <?php elseif ($owner['billing_status'] === 'expired' || $owner['billing_status'] === 'blocked'): ?>
            <p class="notice notice--warn"><?= View::e(Lang::t('owner.billing_expired_note')) ?></p>
        <?php else: ?>
            <p class="billing-card__note"><?= $isVisible ? View::e(Lang::t('owner.billing_visible_note')) : View::e(Lang::t('owner.billing_hidden_note')) ?></p>
        <?php endif; ?>

        <?php if ($owner['billing_status'] !== 'free'): ?>
            <button type="button" class="btn btn--primary btn--large" disabled title="<?= View::e(Lang::t('owner.billing_coming_soon')) ?>">
                <?= View::e(Lang::t('owner.billing_pay_button')) ?>
            </button>
            <p class="billing-card__note"><?= View::e(Lang::t('owner.billing_coming_soon')) ?></p>
        <?php endif; ?>
    </div>
</section>
