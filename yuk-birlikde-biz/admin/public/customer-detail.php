<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'customer'");
$stmt->execute(['id' => $id]);
$customer = $stmt->fetch();

if ($customer === false) {
    redirect_flash('customers.php', 'İstifadəçi tapılmadı.', 'error');
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
    } elseif (in_array($action, ['temp_block', 'block', 'unblock', 'delete'], true)) {
        $newStatus = ['temp_block' => 'temp_blocked', 'block' => 'blocked', 'unblock' => 'active', 'delete' => 'deleted'][$action];
        $db->prepare('UPDATE users SET status = :status WHERE id = :id')->execute(['status' => $newStatus, 'id' => $id]);
        $db->prepare('DELETE FROM sessions WHERE user_id = :id')->execute(['id' => $id]);
        audit_log((int) $session['admin_id'], $action . '_user', 'users', $id);
    }

    redirect_flash("customer-detail.php?id={$id}", 'Yadda saxlanıldı.');
}

$orders = $db->prepare('SELECT id, number, status, created_at FROM orders WHERE customer_id = :id ORDER BY id DESC LIMIT 20');
$orders->execute(['id' => $id]);
$orders = $orders->fetchAll();

$complaints = $db->prepare('SELECT id, subject, status, created_at FROM complaints WHERE user_id = :id ORDER BY id DESC LIMIT 10');
$complaints->execute(['id' => $id]);
$complaints = $complaints->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'temp_blocked' => ['Müvəqqəti bloklanıb', 'warning'], 'blocked' => ['Bloklanıb', 'error'], 'deleted' => ['Silinib', 'muted']];
$orderStatusLabels = ['active' => 'Aktiv', 'waiting' => 'Təklif gözləyir', 'negotiating' => 'Danışıq gedir', 'closed' => 'Bağlanıb', 'cancelled' => 'Ləğv edilib', 'expired' => 'Müddəti bitib'];

admin_header('Müştəri: ' . $customer['first_name'] . ' ' . $customer['last_name'], $session);
[$label, $type] = $statusLabels[$customer['status']] ?? [$customer['status'], 'muted'];
?>
<div class="card">
  <h2>Profil <?= badge($label, $type) ?></h2>
  <form method="post" class="form-row" style="align-items:flex-end;">
    <input type="hidden" name="action" value="update_name">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Ad</label><input class="input" name="first_name" value="<?= h($customer['first_name']) ?>"></div>
    <div class="field"><label>Soyad</label><input class="input" name="last_name" value="<?= h($customer['last_name']) ?>"></div>
    <div class="field"><label>Telefon</label><input class="input" value="+<?= h($customer['phone']) ?>" disabled></div>
    <div class="field"><button class="btn btn-primary" type="submit">Yadda saxla</button></div>
  </form>
  <p class="small-text" style="color:var(--text-muted);">Qeydiyyat: <?= h($customer['created_at']) ?> · Son aktivlik: <?= h($customer['last_seen_at'] ?? '—') ?></p>

  <div style="display:flex;gap:8px;margin-top:12px;">
    <?php if ($customer['status'] !== 'active'): ?>
      <form method="post"><input type="hidden" name="action" value="unblock"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Blokdan çıxar</button></form>
    <?php endif; ?>
    <?php if ($customer['status'] !== 'temp_blocked'): ?>
      <form method="post"><input type="hidden" name="action" value="temp_block"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Müvəqqəti blokla</button></form>
    <?php endif; ?>
    <?php if ($customer['status'] !== 'blocked'): ?>
      <form method="post"><input type="hidden" name="action" value="block"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Tam blokla</button></form>
    <?php endif; ?>
    <?php if ($customer['status'] !== 'deleted'): ?>
      <form method="post" onsubmit="return confirm('Hesab silinsin?');"><input type="hidden" name="action" value="delete"><?= admin_csrf_field($session) ?><button class="btn btn-danger" type="submit">Hesabı sil</button></form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Elanları (<?= count($orders) ?>)</h2>
  <?php if ($orders === []): ?><div class="empty-state">Elan yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Nömrə</th><th>Status</th><th>Tarix</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr><td><?= h($o['number']) ?></td><td><?= badge($orderStatusLabels[$o['status']] ?? $o['status']) ?></td><td><?= h($o['created_at']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Şikayətlər (<?= count($complaints) ?>)</h2>
  <?php if ($complaints === []): ?><div class="empty-state">Şikayət yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Mövzu</th><th>Status</th><th>Tarix</th></tr></thead>
    <tbody>
      <?php foreach ($complaints as $c): ?>
      <tr><td><?= h($c['subject']) ?></td><td><?= badge($c['status']) ?></td><td><?= h($c['created_at']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
