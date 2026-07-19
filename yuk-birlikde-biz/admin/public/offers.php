<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/orders.php';

$session = require_admin();
$db = db();

$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $offerId = (int) ($_POST['offer_id'] ?? 0);

    $stmt = $db->prepare('SELECT * FROM offers WHERE id = :id');
    $stmt->execute(['id' => $offerId]);
    $offer = $stmt->fetch();

    if ($offer !== false) {
        $order = fetch_order_or_404((int) $offer['order_id']);

        if ($offer['status'] === 'selected') {
            $db->prepare('UPDATE orders SET status = "active", selected_offer_id = NULL, reopen_count = reopen_count + 1 WHERE id = :id')
                ->execute(['id' => $order['id']]);
            notify_user((int) $order['customer_id'], 'driver_declined', 'Təklif silindi', 'Admin seçilmiş təklifi sildi, elanınız yenidən aktivdir.', "/elan/{$order['id']}");
            $order['status'] = 'active';
            publish_event('feed', 'listing.reopened', order_feed_payload($order));
            publish_event("user:{$order['customer_id']}", 'selection.cancelled', ['order_id' => $order['id']]);
        }

        $db->prepare('DELETE FROM offers WHERE id = :id')->execute(['id' => $offerId]);

        if (order_offer_count((int) $order['id']) === 0) {
            $db->prepare('UPDATE orders SET status = "active" WHERE id = :id AND status = "waiting"')->execute(['id' => $order['id']]);
        }

        publish_event("listing:{$offer['order_id']}", 'offer.withdrawn', ['offer_id' => $offerId]);
        audit_log((int) $session['admin_id'], 'offer_delete', 'offers', $offerId);
    }

    redirect_flash('offers.php', 'Təklif silindi.');
}

$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 'of.status = :status';
    $params['status'] = $status;
}
$whereSql = implode(' AND ', $where);

$total = (int) db_scalar($db, "SELECT COUNT(*) FROM offers of WHERE {$whereSql}", $params);
$totalPages = max(1, (int) ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT of.id, of.price, of.arrival_time, of.status, of.created_at, of.order_id,
            CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
            o.number AS order_number
     FROM offers of
     JOIN users du ON du.id = of.driver_id
     JOIN orders o ON o.id = of.order_id
     WHERE {$whereSql} ORDER BY of.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$offers = $stmt->fetchAll();

$offerStatusLabels = ['pending' => ['Gözləyir', 'warning'], 'selected' => ['Seçilib', 'success'], 'withdrawn' => ['Geri çəkilib', 'muted'], 'archived' => ['Arxivləşib', 'muted']];

admin_header('Təkliflər', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <select name="status">
      <option value="">Bütün statuslar</option>
      <?php foreach ($offerStatusLabels as $key => [$label, $_]): ?>
        <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrlə</button>
  </form>

  <?php if ($offers === []): ?>
    <div class="empty-state">Nəticə tapılmadı.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Sürücü</th><th>Elan</th><th>Qiymət</th><th>Tarix</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($offers as $of): [$label, $type] = $offerStatusLabels[$of['status']] ?? [$of['status'], 'muted']; ?>
      <tr>
        <td><?= h($of['driver_name']) ?></td>
        <td><a href="order-detail.php?id=<?= (int) $of['order_id'] ?>"><?= h($of['order_number']) ?></a></td>
        <td><?= h((string) $of['price']) ?> AZN</td>
        <td><?= h($of['created_at']) ?></td>
        <td><?= badge($label, $type) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Təklif silinsin?');">
            <input type="hidden" name="offer_id" value="<?= (int) $of['id'] ?>">
            <?= admin_csrf_field($session) ?>
            <button class="btn btn-sm btn-danger" type="submit">Sil</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php paginate($page, $totalPages, 'offers.php?status=' . urlencode($status)); ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
