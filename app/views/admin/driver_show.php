<?php
/** @var array $driver */
/** @var array $vehicleTypes */
/** @var array $jobs */
/** @var array $payments */
use App\Core\Csrf;
use App\Core\Phone;
?>
<a href="/surucular" class="text-soft">← Sürücülər</a>
<h1><?= e($driver['full_name']) ?></h1>
<p class="text-soft"><?= e(Phone::display($driver['phone'])) ?> ·
  <a href="https://wa.me/<?= e($driver['phone']) ?>" target="_blank" style="color:var(--amber)">WhatsApp</a>
</p>

<?php if ($driver['driver_status'] === 'pending'): ?>
  <div class="card">
    <h2>Təsdiq növbəsi</h2>
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
        <button type="submit" class="btn btn-amber btn-block">Təsdiqlə</button>
      </form>
      <form method="post" action="/surucular/<?= (int) $driver['id'] ?>/redd" style="flex:1">
        <?= Csrf::field() ?>
        <input type="text" name="reason" placeholder="Səbəb" style="margin-bottom:8px">
        <button type="submit" class="btn btn-outline btn-block">Geri göndər</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $driver['jobs_done'] ?></div><div class="label">Bağlanmış iş</div></div>
  <div class="stat-card"><div class="num"><?= (int) $driver['cancel_count'] ?></div><div class="label">Ləğv sayı</div></div>
  <div class="stat-card"><div class="num"><?= e($driver['billing_status'] ?? '—') ?></div><div class="label">Billing status</div></div>
  <div class="stat-card"><div class="num"><?= e($driver['trial_until'] ?? $driver['paid_until'] ?? '—') ?></div><div class="label">Bitmə tarixi</div></div>
</div>

<div class="card">
  <h2 style="margin-top:0">İdarə</h2>
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

<h2>İş tarixçəsi</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Marşrut</th><th>Qiymət</th><th>Tarix</th></tr></thead>
  <tbody>
  <?php foreach ($jobs as $j): ?>
    <tr><td><?= e($j['from_name']) ?> → <?= e($j['to_name']) ?></td><td><?= number_format((float) $j['price'], 2) ?> AZN</td><td><?= e(substr($j['created_at'], 0, 10)) ?></td></tr>
  <?php endforeach; ?>
  <?php if ($jobs === []): ?><tr><td colspan="3" class="text-soft">Yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<h2>Ödəniş tarixçəsi</h2>
<div class="table-wrap">
<table class="admin-table">
  <thead><tr><th>Tarix</th><th>Məbləğ</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($payments as $p): ?>
    <tr><td><?= e(substr($p['created_at'], 0, 10)) ?></td><td><?= number_format((float) $p['amount'], 2) ?> <?= e($p['currency']) ?></td><td><?= e($p['status']) ?></td></tr>
  <?php endforeach; ?>
  <?php if ($payments === []): ?><tr><td colspan="3" class="text-soft">Yoxdur</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
