<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/subscription.php';

$session = require_admin();
$db = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT u.*, d.vehicle_id, d.vehicle_other, d.vehicle_size_id, d.rating_avg, d.rating_count, d.completed_count
     FROM users u JOIN drivers d ON d.user_id = u.id WHERE u.id = :id AND u.role = 'driver'"
);
$stmt->execute(['id' => $id]);
$driver = $stmt->fetch();

if ($driver === false) {
    redirect_flash('drivers.php', 'Sürücü tapılmadı.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_name') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        if ($firstName !== '' && $lastName !== '') {
            $db->prepare('UPDATE users SET first_name = :fn, last_name = :ln WHERE id = :id')
                ->execute(['fn' => $firstName, 'ln' => $lastName, 'id' => $id]);
            audit_log((int) $session['admin_id'], 'update_profile', 'users', $id, ['first_name' => $firstName, 'last_name' => $lastName]);
        }
    } elseif ($action === 'update_vehicle') {
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        $vehicleSizeId = (int) ($_POST['vehicle_size_id'] ?? 0);
        $db->prepare('UPDATE drivers SET vehicle_id = :v, vehicle_size_id = :vs WHERE user_id = :id')
            ->execute(['v' => $vehicleId, 'vs' => $vehicleSizeId, 'id' => $id]);
        audit_log((int) $session['admin_id'], 'update_vehicle', 'drivers', $id, ['vehicle_id' => $vehicleId, 'vehicle_size_id' => $vehicleSizeId]);
    } elseif (in_array($action, ['temp_block', 'block', 'unblock', 'delete'], true)) {
        $newStatus = ['temp_block' => 'temp_blocked', 'block' => 'blocked', 'unblock' => 'active', 'delete' => 'deleted'][$action];
        $db->prepare('UPDATE users SET status = :status WHERE id = :id')->execute(['status' => $newStatus, 'id' => $id]);
        $db->prepare('DELETE FROM sessions WHERE user_id = :id')->execute(['id' => $id]);
        audit_log((int) $session['admin_id'], $action . '_user', 'users', $id);
    } elseif ($action === 'subscription_grant') {
        $days = max(1, (int) ($_POST['days'] ?? 30));
        $result = subscription_grant($id, 'admin', $days, (int) $session['admin_id']);
        notify_user($id, 'subscription_activated', 'Abunə aktivləşdi', 'Admin tərəfindən abunəniz aktivləşdirildi.', '/abune', 'system');
        publish_event("user:{$id}", 'subscription.activated', ['ends_at' => $result['ends_at']]);
        audit_log((int) $session['admin_id'], 'subscription_grant', 'subscriptions', $result['id'], ['days' => $days]);
    } elseif (in_array($action, ['subscription_extend', 'subscription_reduce'], true)) {
        $subId = (int) ($_POST['subscription_id'] ?? 0);
        $days = max(1, (int) ($_POST['days'] ?? 1));
        $delta = $action === 'subscription_extend' ? $days : -$days;
        $newEndsAt = subscription_adjust_days($subId, $delta);
        audit_log((int) $session['admin_id'], $action, 'subscriptions', $subId, ['days' => $days, 'new_ends_at' => $newEndsAt]);
    } elseif ($action === 'subscription_cancel') {
        $subId = (int) ($_POST['subscription_id'] ?? 0);
        subscription_cancel($subId);
        notify_user($id, 'subscription_expired', 'Abunə ləğv edildi', 'Abunəniz admin tərəfindən ləğv edildi.', '/abune', 'system');
        publish_event("user:{$id}", 'subscription.expired', []);
        audit_log((int) $session['admin_id'], 'subscription_cancel', 'subscriptions', $subId);
    }

    redirect_flash("driver-detail.php?id={$id}", 'Yadda saxlanıldı.');
}

$vehicles = $db->query('SELECT id, name FROM vehicles ORDER BY sort')->fetchAll();
$vehicleSizes = $db->query('SELECT id, code, dimensions FROM vehicle_sizes ORDER BY sort')->fetchAll();

$currentSub = $db->prepare("SELECT id, ends_at FROM subscriptions WHERE driver_id = :id AND status = 'active' AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1");
$currentSub->execute(['id' => $id]);
$currentSub = $currentSub->fetch();

$subHistory = $db->prepare('SELECT id, source, starts_at, ends_at, status, created_at FROM subscriptions WHERE driver_id = :id ORDER BY id DESC LIMIT 20');
$subHistory->execute(['id' => $id]);
$subHistory = $subHistory->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'temp_blocked' => ['Müvəqqəti bloklanıb', 'warning'], 'blocked' => ['Bloklanıb', 'error'], 'deleted' => ['Silinib', 'muted']];

admin_header('Sürücü: ' . $driver['first_name'] . ' ' . $driver['last_name'], $session);
[$label, $type] = $statusLabels[$driver['status']] ?? [$driver['status'], 'muted'];
?>
<div class="card">
  <h2>Profil <?= badge($label, $type) ?></h2>
  <form method="post" class="form-row" style="align-items:flex-end;">
    <input type="hidden" name="action" value="update_name">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Ad</label><input class="input" name="first_name" value="<?= h($driver['first_name']) ?>"></div>
    <div class="field"><label>Soyad</label><input class="input" name="last_name" value="<?= h($driver['last_name']) ?>"></div>
    <div class="field"><label>Telefon</label><input class="input" value="+<?= h($driver['phone']) ?>" disabled></div>
    <div class="field"><button class="btn btn-primary" type="submit">Yadda saxla</button></div>
  </form>
  <p class="small-text" style="color:var(--text-muted);">
    Reytinq: <?= $driver['rating_avg'] !== null ? h((string) $driver['rating_avg']) . " ({$driver['rating_count']})" : '—' ?> ·
    Tamamlanmış sifariş: <?= (int) $driver['completed_count'] ?> ·
    Qeydiyyat: <?= h($driver['created_at']) ?>
  </p>

  <div style="display:flex;gap:8px;margin-top:12px;">
    <?php if ($driver['status'] !== 'active'): ?>
      <form method="post"><input type="hidden" name="action" value="unblock"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Blokdan çıxar</button></form>
    <?php endif; ?>
    <?php if ($driver['status'] !== 'temp_blocked'): ?>
      <form method="post"><input type="hidden" name="action" value="temp_block"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Müvəqqəti blokla</button></form>
    <?php endif; ?>
    <?php if ($driver['status'] !== 'blocked'): ?>
      <form method="post"><input type="hidden" name="action" value="block"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Tam blokla</button></form>
    <?php endif; ?>
    <?php if ($driver['status'] !== 'deleted'): ?>
      <form method="post" onsubmit="return confirm('Hesab silinsin?');"><input type="hidden" name="action" value="delete"><?= admin_csrf_field($session) ?><button class="btn btn-danger" type="submit">Hesabı sil</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Avtomobil</h2>
  <form method="post" class="form-row" style="align-items:flex-end;">
    <input type="hidden" name="action" value="update_vehicle">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Model</label>
      <select name="vehicle_id">
        <?php foreach ($vehicles as $v): ?>
          <option value="<?= (int) $v['id'] ?>" <?= $v['id'] == $driver['vehicle_id'] ? 'selected' : '' ?>><?= h($v['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Ölçü</label>
      <select name="vehicle_size_id">
        <?php foreach ($vehicleSizes as $vs): ?>
          <option value="<?= (int) $vs['id'] ?>" <?= $vs['id'] == $driver['vehicle_size_id'] ? 'selected' : '' ?>><?= h($vs['code']) ?> (<?= h($vs['dimensions']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><button class="btn btn-primary" type="submit">Yadda saxla</button></div>
  </form>
</div>

<div class="card">
  <h2>Abunə</h2>
  <?php if ($currentSub): ?>
    <p><?= badge('Aktiv — bitmə: ' . $currentSub['ends_at'], 'success') ?></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="post" class="form-row" style="align-items:flex-end;gap:8px;">
        <input type="hidden" name="action" value="subscription_extend">
        <input type="hidden" name="subscription_id" value="<?= (int) $currentSub['id'] ?>">
        <?= admin_csrf_field($session) ?>
        <div class="field" style="margin:0;"><input class="input" type="number" name="days" value="7" min="1" style="width:80px;"></div>
        <div class="field" style="margin:0;"><button class="btn" type="submit">Uzat (gün)</button></div>
      </form>
      <form method="post" class="form-row" style="align-items:flex-end;gap:8px;">
        <input type="hidden" name="action" value="subscription_reduce">
        <input type="hidden" name="subscription_id" value="<?= (int) $currentSub['id'] ?>">
        <?= admin_csrf_field($session) ?>
        <div class="field" style="margin:0;"><input class="input" type="number" name="days" value="7" min="1" style="width:80px;"></div>
        <div class="field" style="margin:0;"><button class="btn" type="submit">Azalt (gün)</button></div>
      </form>
      <form method="post" onsubmit="return confirm('Abunə ləğv edilsin?');">
        <input type="hidden" name="action" value="subscription_cancel">
        <input type="hidden" name="subscription_id" value="<?= (int) $currentSub['id'] ?>">
        <?= admin_csrf_field($session) ?>
        <button class="btn btn-danger" type="submit">Ləğv et</button>
      </form>
    </div>
  <?php else: ?>
    <p><?= badge('Aktiv abunə yoxdur', 'muted') ?></p>
    <form method="post" class="form-row" style="align-items:flex-end;gap:8px;">
      <input type="hidden" name="action" value="subscription_grant">
      <?= admin_csrf_field($session) ?>
      <div class="field" style="margin:0;"><label>Müddət (gün)</label><input class="input" type="number" name="days" value="30" min="1" style="width:100px;"></div>
      <div class="field" style="margin:0;"><button class="btn btn-primary" type="submit">Pulsuz abunə ver</button></div>
    </form>
  <?php endif; ?>

  <h2 style="margin-top:20px;">Abunə tarixçəsi</h2>
  <?php if ($subHistory === []): ?><div class="empty-state">Tarixçə yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Mənbə</th><th>Başlanğıc</th><th>Bitmə</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($subHistory as $s): ?>
      <tr><td><?= h($s['source']) ?></td><td><?= h($s['starts_at']) ?></td><td><?= h($s['ends_at']) ?></td><td><?= badge($s['status'], $s['status'] === 'active' ? 'success' : 'muted') ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
