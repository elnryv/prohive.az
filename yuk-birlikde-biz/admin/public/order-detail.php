<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/orders.php';

$session = require_admin();
$db = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT o.*, ct.name AS cargo_type_name, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.phone AS customer_phone
     FROM orders o
     JOIN cargo_types ct ON ct.id = o.cargo_type_id
     JOIN users u ON u.id = o.customer_id
     WHERE o.id = :id"
);
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if ($order === false) {
    redirect_flash('orders.php', 'Elan tapılmadı.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'close') {
        $db->prepare("UPDATE orders SET status = 'closed' WHERE id = :id")->execute(['id' => $id]);
        publish_event('feed', 'listing.removed', ['id' => $id, 'reason' => 'closed']);
        notify_user((int) $order['customer_id'], 'order_closed', 'Sifariş bağlandı', 'Admin tərəfindən sifarişiniz bağlandı.', "/elan/{$id}");
        if ($order['selected_offer_id']) {
            $driverId = db_scalar($db, 'SELECT driver_id FROM offers WHERE id = :id', ['id' => $order['selected_offer_id']]);
            if ($driverId) {
                notify_user((int) $driverId, 'order_closed', 'Sifariş bağlandı', 'Admin tərəfindən sifariş bağlandı.', "/elan/{$id}");
                publish_event("user:{$driverId}", 'order.closed', ['order_id' => $id]);
            }
        }
        audit_log((int) $session['admin_id'], 'order_close', 'orders', $id);
    } elseif ($action === 'reopen') {
        $ttlHours = settings_get_int('order_ttl_hours', 72);
        $db->prepare("UPDATE orders SET status = 'active', selected_offer_id = NULL, expires_at = DATE_ADD(NOW(), INTERVAL :h HOUR) WHERE id = :id")
            ->execute(['h' => $ttlHours, 'id' => $id]);
        publish_event('feed', 'listing.reopened', order_feed_payload($order));
        notify_user((int) $order['customer_id'], 'order_reopened', 'Elan yenidən aktivdir', 'Admin tərəfindən elanınız yenidən aktivləşdirildi.', "/elan/{$id}");
        audit_log((int) $session['admin_id'], 'order_reopen', 'orders', $id);
    } elseif ($action === 'delete') {
        $images = $db->prepare('SELECT path, thumb_path FROM order_images WHERE order_id = :id');
        $images->execute(['id' => $id]);
        foreach ($images->fetchAll() as $img) {
            foreach ([$img['path'], $img['thumb_path']] as $relPath) {
                if ($relPath) {
                    @unlink(__DIR__ . '/../../storage/uploads/' . $relPath);
                }
            }
        }
        $db->prepare('DELETE FROM order_images WHERE order_id = :id')->execute(['id' => $id]);
        $db->prepare('DELETE FROM offers WHERE order_id = :id')->execute(['id' => $id]);
        $db->prepare('DELETE FROM orders WHERE id = :id')->execute(['id' => $id]);
        publish_event('feed', 'listing.removed', ['id' => $id, 'reason' => 'admin_deleted']);
        notify_user((int) $order['customer_id'], 'order_deleted', 'Elan silindi', 'Elanınız admin tərəfindən silindi.', null);
        audit_log((int) $session['admin_id'], 'order_delete', 'orders', $id);
        redirect_flash('orders.php', 'Elan silindi.');
    }

    redirect_flash("order-detail.php?id={$id}", 'Yadda saxlanıldı.');
}

$offers = $db->prepare(
    "SELECT of.*, CONCAT(u.first_name, ' ', u.last_name) AS driver_name, u.phone AS driver_phone
     FROM offers of JOIN users u ON u.id = of.driver_id
     WHERE of.order_id = :id ORDER BY of.id DESC"
);
$offers->execute(['id' => $id]);
$offers = $offers->fetchAll();

$statusLabels = ['active' => ['Aktiv', 'success'], 'waiting' => ['Təklif gözləyir', 'warning'], 'negotiating' => ['Danışıq gedir', 'warning'], 'closed' => ['Bağlanıb', 'success'], 'cancelled' => ['Ləğv olunub', 'muted'], 'expired' => ['Müddəti bitib', 'error']];
$offerStatusLabels = ['pending' => 'Gözləyir', 'selected' => 'Seçilib', 'withdrawn' => 'Geri çəkilib', 'archived' => 'Arxivləşib'];

admin_header('Elan #' . $order['number'], $session);
[$label, $type] = $statusLabels[$order['status']] ?? [$order['status'], 'muted'];
?>
<div class="card">
  <h2><?= h($order['number']) ?> <?= badge($label, $type) ?></h2>
  <p class="small-text">
    Müştəri: <a href="customer-detail.php?id=<?= (int) $order['customer_id'] ?>"><?= h($order['customer_name']) ?></a> (+<?= h($order['customer_phone']) ?>)<br>
    Yük növü: <?= h($order['cargo_type_name']) ?><br>
    Marşrut: <?= h($order['from_city']) ?><?= $order['from_district'] ? ', ' . h($order['from_district']) : '' ?> → <?= h($order['to_city']) ?><?= $order['to_district'] ? ', ' . h($order['to_district']) : '' ?><br>
    Tarix: <?= h($order['date_time']) ?><br>
    <?= $order['note'] ? 'Qeyd: ' . h($order['note']) . '<br>' : '' ?>
    Yaradılıb: <?= h($order['created_at']) ?>
  </p>
  <div style="display:flex;gap:8px;">
    <?php if (!in_array($order['status'], ['closed'], true)): ?>
      <form method="post"><input type="hidden" name="action" value="close"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Bağla</button></form>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['expired', 'cancelled', 'closed'], true)): ?>
      <form method="post"><input type="hidden" name="action" value="reopen"><?= admin_csrf_field($session) ?><button class="btn" type="submit">Yenidən aktiv et</button></form>
    <?php endif; ?>
    <form method="post" onsubmit="return confirm('Elan tam silinsin? Bu geri qaytarıla bilməz.');">
      <input type="hidden" name="action" value="delete"><?= admin_csrf_field($session) ?><button class="btn btn-danger" type="submit">Sil</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Təkliflər (<?= count($offers) ?>)</h2>
  <?php if ($offers === []): ?><div class="empty-state">Təklif yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Sürücü</th><th>Qiymət</th><th>Gəliş vaxtı</th><th>Status</th><th>Tarix</th></tr></thead>
    <tbody>
      <?php foreach ($offers as $of): ?>
      <tr>
        <td><a href="driver-detail.php?id=<?= (int) $of['driver_id'] ?>"><?= h($of['driver_name']) ?></a> (+<?= h($of['driver_phone']) ?>)</td>
        <td><?= h((string) $of['price']) ?> AZN</td>
        <td><?= h($of['arrival_time']) ?></td>
        <td><?= badge($offerStatusLabels[$of['status']] ?? $of['status']) ?></td>
        <td><?= h($of['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
