<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

$statusLabels = ['new' => ['Yeni', 'error'], 'reviewing' => ['Baxılır', 'warning'], 'answered' => ['Cavablandırıldı', 'success'], 'closed' => ['Bağlanıb', 'muted']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    $stmt = $db->prepare('SELECT * FROM complaints WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $complaint = $stmt->fetch();

    if ($complaint !== false) {
        if ($action === 'reply') {
            $reply = trim((string) ($_POST['reply'] ?? ''));
            if ($reply !== '') {
                $db->prepare("UPDATE complaints SET admin_reply = :reply, status = 'answered' WHERE id = :id")
                    ->execute(['reply' => $reply, 'id' => $id]);
                notify_user((int) $complaint['user_id'], 'complaint_reply', 'Şikayətinizə cavab', $reply, null, 'system');
                audit_log((int) $session['admin_id'], 'complaint_reply', 'complaints', $id);
            }
        } elseif (in_array($action, ['reviewing', 'closed'], true)) {
            $db->prepare('UPDATE complaints SET status = :status WHERE id = :id')->execute(['status' => $action, 'id' => $id]);
            audit_log((int) $session['admin_id'], 'complaint_status_' . $action, 'complaints', $id);
        }
    }

    redirect_flash('complaints.php', 'Yadda saxlanıldı.');
}

$status = (string) ($_GET['status'] ?? '');
$where = $status !== '' ? 'WHERE c.status = :status' : '';
$params = $status !== '' ? ['status' => $status] : [];

$stmt = $db->prepare(
    "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.role
     FROM complaints c JOIN users u ON u.id = c.user_id
     {$where} ORDER BY c.id DESC LIMIT 50"
);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

admin_header('Şikayətlər', $session);
?>
<div class="card">
  <form class="filters" method="get">
    <select name="status">
      <option value="">Bütün statuslar</option>
      <?php foreach ($statusLabels as $key => [$label, $_]): ?>
        <option value="<?= h($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrlə</button>
  </form>

  <?php if ($complaints === []): ?>
    <div class="empty-state">Şikayət yoxdur.</div>
  <?php else: ?>
    <?php foreach ($complaints as $c): [$label, $type] = $statusLabels[$c['status']] ?? [$c['status'], 'muted']; ?>
    <div class="card" style="border-color:var(--border);">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <strong><?= h($c['subject']) ?></strong> <?= badge($label, $type) ?>
          <div class="small-text" style="color:var(--text-muted);"><?= h($c['user_name']) ?> (<?= h($c['role']) ?>) · <?= h($c['created_at']) ?></div>
        </div>
      </div>
      <p class="small-text" style="margin-top:8px;"><?= nl2br(h($c['message'])) ?></p>
      <?php if ($c['admin_reply']): ?>
        <div class="small-text" style="background:var(--primary-soft);padding:8px 12px;border-radius:8px;margin-top:8px;">
          <strong>Cavab:</strong> <?= nl2br(h($c['admin_reply'])) ?>
        </div>
      <?php endif; ?>

      <form method="post" style="margin-top:12px;">
        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <input type="hidden" name="action" value="reply">
        <?= admin_csrf_field($session) ?>
        <textarea name="reply" rows="2" placeholder="Cavab yazın…"></textarea>
        <button class="btn btn-sm btn-primary" type="submit" style="margin-top:8px;">Cavab göndər</button>
      </form>
      <div style="display:flex;gap:8px;margin-top:8px;">
        <?php if ($c['status'] !== 'reviewing'): ?>
          <form method="post"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="reviewing"><?= admin_csrf_field($session) ?><button class="btn btn-sm" type="submit">Baxılır et</button></form>
        <?php endif; ?>
        <?php if ($c['status'] !== 'closed'): ?>
          <form method="post"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="action" value="closed"><?= admin_csrf_field($session) ?><button class="btn btn-sm" type="submit">Bağla</button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php
admin_footer();
