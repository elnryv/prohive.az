<?php
/** @var bool $paymentsEnabled */
/** @var array $user */
/** @var float $amount */
/** @var string $currency */
/** @var bool $payriffConfigured */
/** @var array $history */
use App\Core\Csrf;
?>
<div class="container">
  <h1><?= e(t('billing.title')) ?></h1>

  <?php if (!$paymentsEnabled): ?>
    <div class="card" style="border-color:var(--ok)">
      <p><?= e(t('billing.free_notice')) ?></p>
    </div>
  <?php else: ?>
    <div class="card">
      <?php if ($user['billing_status'] === 'free'): ?>
        <p class="chip chip-ok"><?= e(t('billing.status_free')) ?></p>
      <?php elseif ($user['billing_status'] === 'trial'): ?>
        <p class="chip <?= $user['trial_until'] >= date('Y-m-d') ? 'chip-active' : 'chip-muted' ?>">
          <?= e(t('billing.status_trial', ['date' => $user['trial_until']])) ?>
        </p>
      <?php elseif ($user['billing_status'] === 'paid'): ?>
        <p class="chip <?= $user['paid_until'] >= date('Y-m-d') ? 'chip-ok' : 'chip-muted' ?>">
          <?= e(t('billing.status_paid', ['date' => $user['paid_until']])) ?>
        </p>
      <?php else: ?>
        <p class="chip chip-muted"><?= e(t('billing.status_expired')) ?></p>
      <?php endif; ?>

      <p class="num" style="font-size:28px;margin-top:12px"><?= number_format($amount, 2) ?> <?= e($currency) ?></p>
      <p class="text-soft"><?= e(t('billing.monthly_note')) ?></p>

      <?php if (!$payriffConfigured): ?>
        <p class="banner"><?= e(t('billing.coming_soon')) ?></p>
      <?php else: ?>
        <form method="post" action="/surucu/odenis/ode">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-amber btn-block"><?= e(t('billing.pay_now')) ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($history !== []): ?>
    <h2 style="margin-top:24px"><?= e(t('billing.history')) ?></h2>
    <?php foreach ($history as $p): ?>
      <div class="card" style="display:flex;justify-content:space-between;align-items:center">
        <span><?= e(substr($p['created_at'], 0, 10)) ?></span>
        <span class="num"><?= number_format((float) $p['amount'], 2) ?> <?= e($p['currency']) ?></span>
        <span class="chip <?= $p['status'] === 'paid' ? 'chip-ok' : ($p['status'] === 'failed' ? 'chip-muted' : 'chip-warn') ?>">
          <?= e(t('billing.payment_status_' . $p['status'])) ?>
        </span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
