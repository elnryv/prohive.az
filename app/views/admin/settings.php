<?php
/** @var array $settings */
/** @var array $categories */
/** @var array $locations */
/** @var array $vehicleTypes */
use App\Core\Csrf;

$paymentsOn = $settings['payments_enabled'] === '1';
$sifreXeta = $_GET['sifre_xeta'] ?? null;
$sifreOk = ($_GET['sifre'] ?? '') === 'ok';
?>
<h1 style="display:flex;align-items:center;gap:8px"><?= icon('settings') ?> Parametrlər</h1>

<div class="card">
  <h2 style="margin-top:0">Şifrəni dəyiş</h2>
  <?php if ($sifreOk): ?>
    <div class="banner" style="border-left-color:var(--ok)">Şifrə dəyişdirildi.</div>
  <?php endif; ?>
  <?php if ($sifreXeta !== null): ?>
    <div class="banner banner-error">
      <?= e(match ($sifreXeta) {
          'cari_yanlis' => 'Cari şifrə yanlışdır.',
          'qisa' => 'Yeni şifrə minimum 6 simvol olmalıdır.',
          'uygun_deyil' => 'Yeni şifrələr üst-üstə düşmür.',
          default => 'Xəta baş verdi.',
      }) ?>
    </div>
  <?php endif; ?>
  <form method="post" action="/parametrler/sifre">
    <?= Csrf::field() ?>
    <div class="field">
      <label>Cari şifrə</label>
      <input type="password" name="current_password" required>
    </div>
    <div class="field">
      <label>Yeni şifrə</label>
      <input type="password" name="new_password" required minlength="6">
    </div>
    <div class="field">
      <label>Yeni şifrə (təkrar)</label>
      <input type="password" name="new_password_confirm" required minlength="6">
    </div>
    <button type="submit" class="btn btn-amber">Şifrəni dəyiş</button>
  </form>
</div>

<div class="card">
  <h2 style="margin-top:0">Abunə sistemi</h2>
  <div class="toggle-switch <?= $paymentsOn ? 'on' : 'off' ?>">
    <strong><?= $paymentsOn ? 'AKTİV' : 'SÖNÜLÜ' ?></strong>
    <form method="post" action="/parametrler/odenis-toggle" style="margin:0">
      <?= Csrf::field() ?>
      <button type="submit" class="btn btn-sm <?= $paymentsOn ? 'btn-outline' : 'btn-amber' ?>">
        <?= $paymentsOn ? 'Söndür' : 'Yandır' ?>
      </button>
    </form>
  </div>
  <p class="text-soft" style="margin-top:8px">
    Söndürüləndə bütün sürücülər ANINDA aktiv sayılır. Yandırılanda expired sürücülərə
    <?= (int) $settings['grace_days'] ?> günlük keçid güzəşti verilir.
  </p>
</div>

<div class="card">
  <h2 style="margin-top:0">Ümumi parametrlər</h2>
  <form method="post" action="/parametrler/umumi">
    <?= Csrf::field() ?>
    <div class="field">
      <label>Ümumi aylıq qiymət (AZN)</label>
      <input type="number" step="0.01" name="default_monthly_price" value="<?= e($settings['default_monthly_price']) ?>">
    </div>
    <div class="field">
      <label>Trial günü</label>
      <input type="number" name="trial_days" value="<?= e($settings['trial_days']) ?>">
    </div>
    <div class="field">
      <label>Grace günü</label>
      <input type="number" name="grace_days" value="<?= e($settings['grace_days']) ?>">
    </div>
    <div class="field">
      <label>Elanın avto-bağlanma saatı</label>
      <input type="number" name="listing_auto_close_hours" value="<?= e($settings['listing_auto_close_hours']) ?>">
    </div>
    <div class="field">
      <label>Dəstək nömrəsi</label>
      <input type="text" name="support_phone" value="<?= e($settings['support_phone']) ?>">
    </div>
    <button type="submit" class="btn btn-amber">Yadda saxla</button>
  </form>
</div>

<?php
$dictionaries = [
    'categories' => ['Kateqoriyalar', $categories, true],
    'locations' => ['Lokasiyalar', $locations, false],
    'vehicle_types' => ['Maşın növləri', $vehicleTypes, false],
];
foreach ($dictionaries as $table => [$title, $rows, $hasHint]):
?>
<div class="card">
  <h2 style="margin-top:0"><?= e($title) ?></h2>
  <div class="table-wrap">
  <table class="admin-table">
    <thead><tr><th>AZ</th><th>RU</th><th>EN</th><?php if ($table === 'locations'): ?><th>Bakı?</th><?php endif; ?><th>Aktiv</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['name_az']) ?></td>
        <td><?= e($row['name_ru']) ?></td>
        <td><?= e($row['name_en']) ?></td>
        <?php if ($table === 'locations'): ?><td><?= (int) $row['is_baku'] === 1 ? 'Bəli' : 'Xeyr' ?></td><?php endif; ?>
        <td><?= (int) $row['is_active'] === 1 ? icon('check', 'icon', 14) : '—' ?></td>
        <td>
          <form method="post" action="/parametrler/lugetler/<?= $table ?>/<?= (int) $row['id'] ?>/toggle">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-sm"><?= (int) $row['is_active'] === 1 ? 'Deaktiv et' : 'Aktiv et' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <form method="post" action="/parametrler/lugetler/<?= $table ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
    <?= Csrf::field() ?>
    <input type="text" name="name_az" placeholder="Ad (AZ)" required style="width:auto;flex:1">
    <input type="text" name="name_ru" placeholder="Ad (RU)" required style="width:auto;flex:1">
    <input type="text" name="name_en" placeholder="Ad (EN)" required style="width:auto;flex:1">
    <?php if ($table === 'categories'): ?>
      <input type="text" name="icon" placeholder="İkon (emoji)" style="width:auto;max-width:100px">
    <?php elseif ($table === 'locations'): ?>
      <label style="display:flex;align-items:center;gap:6px;width:auto"><input type="checkbox" name="is_baku" value="1" style="width:auto;min-height:auto">Bakı</label>
    <?php endif; ?>
    <button type="submit" class="btn btn-sm"><?= icon('plus', 'icon', 14) ?> Əlavə et</button>
  </form>
</div>
<?php endforeach; ?>
