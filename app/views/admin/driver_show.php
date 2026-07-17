<?php
/** @var array $driver */
/** @var array $vehicleTypes */
/** @var array $jobs */
/** @var array $payments */
use App\Core\Csrf;
use App\Core\Phone;
?>
<a href="/surucular" class="text-soft" style="display:inline-flex;align-items:center;gap:4px"><?= icon('chevron-left', 'icon', 16) ?> Sürücülər</a>

<div style="display:flex;align-items:center;gap:12px;margin:12px 0">
  <?php if (!empty($driver['profile_photo'])): ?>
    <img src="/uploads/profiles/<?= e($driver['profile_photo']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:50%">
  <?php else: ?>
    <div style="width:56px;height:56px;border-radius:50%;background:var(--card-hi);display:flex;align-items:center;justify-content:center;color:var(--txt-soft)"><?= icon('user', 'icon', 26) ?></div>
  <?php endif; ?>
  <div>
    <h1 style="margin:0"><?= e($driver['full_name']) ?></h1>
    <p class="text-soft" style="margin:2px 0;display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:4px"><?= icon('phone', 'icon', 14) ?> <?= e(Phone::display($driver['phone'])) ?></span>
      <a href="https://wa.me/<?= e($driver['phone']) ?>" target="_blank" style="color:var(--amber);display:inline-flex;align-items:center;gap:4px"><?= icon('whatsapp', 'icon', 14) ?> WhatsApp</a>
    </p>
  </div>
</div>

<?php if ($driver['driver_status'] === 'pending'): ?>
  <div class="card">
    <h2 style="margin-top:0">Təsdiq növbəsi</h2>
    <?php if ($driver['vehicle_photo']): ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php foreach (explode(',', $driver['vehicle_photo']) as $photo): ?>
          <img src="/uploads/vehicles/<?= e($photo) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:8px">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p><?= e($driver['vehicle_note'] ?? '') ?></p>
    <div style="display:flex;gap:8px;margin-top:12px">
      <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/tesdiqle" style="flex:1">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-amber btn-block"><?= icon('check-circle', 'icon', 16) ?> Təsdiqlə</button>
      </form>
      <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/redd" style="flex:1">
        <?= Csrf::field() ?>
        <input type="text" name="reason" placeholder="Səbəb" style="margin-bottom:8px">
        <button type="submit" class="btn btn-outline btn-block"><?= icon('close', 'icon', 16) ?> Geri göndər</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $driver['jobs_done'] ?></div><div class="label">Bağlanmış iş</div></div>
  <div class="stat-card"><div class="num"><?= (int) $driver['cancel_count'] ?></div><div class="label">Ləğv sayı</div></div>
  <div class="stat-card"><div class="num" style="font-size:16px"><?= e($driver['billing_status'] ?? '—') ?></div><div class="label">Billing status</div></div>
  <div class="stat-card"><div class="num" style="font-size:16px"><?= e($driver['trial_until'] ?? $driver['paid_until'] ?? '—') ?></div><div class="label">Bitmə tarixi</div></div>
</div>

<div class="card">
  <h2 style="margin-top:0;display:flex;align-items:center;gap:6px"><?= icon('settings', 'icon', 18) ?> İdarə</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/odenis-tipi">
      <?= Csrf::field() ?>
      <button type="submit" class="btn <?= $driver['billing_status'] === 'free' ? 'btn-outline' : 'btn-amber' ?>">
        <?= $driver['billing_status'] === 'free' ? 'Pulsuzu ləğv et (trial-a qaytar)' : 'Pulsuz et' ?>
      </button>
    </form>
    <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/blokla">
      <?= Csrf::field() ?>
      <button type="submit" class="btn btn-outline"><?= (int) $driver['is_blocked'] === 1 ? 'Blokdan çıxar' : 'Blokla' ?></button>
    </form>
  </div>
  <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/qiymet" style="display:flex;gap:8px;align-items:flex-end">
    <?= Csrf::field() ?>
    <div class="field" style="margin:0;flex:1">
      <label>Fərdi qiymət (AZN, boş = ümumi qiymət)</label>
      <input type="number" step="0.01" name="custom_price" value="<?= e((string) ($driver['custom_price'] ?? '')) ?>">
    </div>
    <button type="submit" class="btn btn-sm">Yadda saxla</button>
  </form>
</div>

<h2 style="display:flex;align-items:center;gap:6px"><?= icon('truck', 'icon', 18) ?> İş tarixçəsi</h2>
<?php if ($jobs === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($jobs as $j): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= e($j['from_name']) ?> → <?= e($j['to_name']) ?></span>
        <span class="chip chip-active"><?= number_format((float) $j['price'], 2) ?> AZN</span>
      </div>
      <div class="admin-row-meta"><span><?= icon('calendar', 'icon', 14) ?> <?= e(substr($j['created_at'], 0, 10)) ?></span></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2 style="margin-top:20px;display:flex;align-items:center;gap:6px"><?= icon('wallet', 'icon', 18) ?> Ödəniş tarixçəsi</h2>
<?php if ($payments === []): ?>
  <div class="empty-state"><p>Yoxdur</p></div>
<?php else: ?>
<div class="admin-list">
  <?php foreach ($payments as $p): ?>
    <div class="admin-row">
      <div class="admin-row-top">
        <span class="admin-row-title"><?= number_format((float) $p['amount'], 2) ?> <?= e($p['currency']) ?></span>
        <span class="chip <?= $p['status'] === 'paid' ? 'chip-ok' : 'chip-muted' ?>"><?= e($p['status']) ?></span>
      </div>
      <div class="admin-row-meta"><span><?= icon('calendar', 'icon', 14) ?> <?= e(substr($p['created_at'], 0, 10)) ?></span></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
