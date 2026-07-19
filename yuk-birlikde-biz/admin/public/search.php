<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$q = trim((string) ($_GET['q'] ?? ''));
$like = "%{$q}%";

$customers = $orders = $offers = $payments = $complaints = [];

if ($q !== '') {
    $stmt = $db->prepare(
        "SELECT id, first_name, last_name, phone, role FROM users
         WHERE (CONCAT(first_name, ' ', last_name) LIKE :q1 OR phone LIKE :q2) AND role IN ('customer','driver')
         LIMIT 10"
    );
    $stmt->execute(['q1' => $like, 'q2' => $like]);
    $customers = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, number, status FROM orders WHERE number LIKE :q LIMIT 10");
    $stmt->execute(['q' => $like]);
    $orders = $stmt->fetchAll();

    $stmt = $db->prepare(
        "SELECT o.id, o.price, o.order_id, ord.number AS order_number, CONCAT(u.first_name, ' ', u.last_name) AS driver_name
         FROM offers o JOIN users u ON u.id = o.driver_id JOIN orders ord ON ord.id = o.order_id
         WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :q OR ord.number LIKE :q2 LIMIT 10"
    );
    $stmt->execute(['q' => $like, 'q2' => $like]);
    $offers = $stmt->fetchAll();

    $stmt = $db->prepare(
        "SELECT p.id, p.amount, p.currency, p.payriff_tx_id, p.driver_id, CONCAT(u.first_name, ' ', u.last_name) AS driver_name
         FROM payments p JOIN users u ON u.id = p.driver_id
         WHERE p.payriff_tx_id LIKE :q LIMIT 10"
    );
    $stmt->execute(['q' => $like]);
    $payments = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, user_id, subject, status FROM complaints WHERE subject LIKE :q1 OR message LIKE :q2 LIMIT 10");
    $stmt->execute(['q1' => $like, 'q2' => $like]);
    $complaints = $stmt->fetchAll();
}

admin_header('Qlobal axtarış', $session);
?>
<div class="card">
  <form method="get">
    <input class="input" type="text" name="q" placeholder="Ad, telefon, elan nömrəsi, tranzaksiya…" value="<?= h($q) ?>" autofocus>
  </form>
</div>

<?php if ($q !== ''): ?>
<div class="card">
  <h2>İstifadəçilər (<?= count($customers) ?>)</h2>
  <?php if ($customers === []): ?><div class="empty-state">Nəticə yoxdur.</div><?php else: ?>
  <table><tbody>
    <?php foreach ($customers as $c): ?>
    <tr><td><?= h($c['first_name'] . ' ' . $c['last_name']) ?></td><td>+<?= h($c['phone']) ?></td><td><?= h($c['role']) ?></td>
      <td><a class="btn btn-sm" href="<?= $c['role'] === 'driver' ? 'driver-detail.php' : 'customer-detail.php' ?>?id=<?= (int) $c['id'] ?>">Bax</a></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Elanlar (<?= count($orders) ?>)</h2>
  <?php if ($orders === []): ?><div class="empty-state">Nəticə yoxdur.</div><?php else: ?>
  <table><tbody>
    <?php foreach ($orders as $o): ?>
    <tr><td><?= h($o['number']) ?></td><td><?= h($o['status']) ?></td><td><a class="btn btn-sm" href="order-detail.php?id=<?= (int) $o['id'] ?>">Bax</a></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Təkliflər (<?= count($offers) ?>)</h2>
  <?php if ($offers === []): ?><div class="empty-state">Nəticə yoxdur.</div><?php else: ?>
  <table><tbody>
    <?php foreach ($offers as $o): ?>
    <tr><td><?= h($o['driver_name']) ?></td><td><?= h($o['order_number']) ?></td><td><?= h((string) $o['price']) ?> AZN</td><td><a class="btn btn-sm" href="order-detail.php?id=<?= (int) $o['order_id'] ?>">Bax</a></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Ödənişlər (<?= count($payments) ?>)</h2>
  <?php if ($payments === []): ?><div class="empty-state">Nəticə yoxdur.</div><?php else: ?>
  <table><tbody>
    <?php foreach ($payments as $p): ?>
    <tr><td><?= h($p['payriff_tx_id']) ?></td><td><?= h($p['driver_name']) ?></td><td><?= h((string) $p['amount']) ?> <?= h($p['currency']) ?></td><td><a class="btn btn-sm" href="driver-detail.php?id=<?= (int) $p['driver_id'] ?>">Bax</a></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Şikayətlər (<?= count($complaints) ?>)</h2>
  <?php if ($complaints === []): ?><div class="empty-state">Nəticə yoxdur.</div><?php else: ?>
  <table><tbody>
    <?php foreach ($complaints as $c): ?>
    <tr><td><?= h($c['subject']) ?></td><td><?= h($c['status']) ?></td><td><a class="btn btn-sm" href="complaints.php">Bax</a></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php
admin_footer();
