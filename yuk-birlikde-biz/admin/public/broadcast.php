<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/broadcast.php';

$session = require_admin();
$db = db();

$audiences = ['all' => 'Hamıya', 'customers' => 'Yalnız müştərilərə', 'drivers' => 'Yalnız sürücülərə', 'active_subs' => 'Yalnız aktiv abunəsi olanlara', 'expired_subs' => 'Yalnız abunəsi bitənlərə'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);

    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $audience = (string) ($_POST['audience'] ?? 'all');
    $scheduledAt = trim((string) ($_POST['scheduled_at'] ?? '')) ?: null;

    if ($title === '' || $body === '' || !array_key_exists($audience, $audiences)) {
        redirect_flash('broadcast.php', 'Başlıq, mətn və auditoriya tələb olunur.', 'error');
    }

    $stmt = $db->prepare(
        'INSERT INTO broadcast_log (admin_id, audience, title, body, scheduled_at) VALUES (:admin_id, :audience, :title, :body, :scheduled_at)'
    );
    $stmt->execute(['admin_id' => $session['admin_id'], 'audience' => $audience, 'title' => substr($title, 0, 160), 'body' => substr($body, 0, 255), 'scheduled_at' => $scheduledAt]);
    $broadcastId = (int) $db->lastInsertId();

    audit_log((int) $session['admin_id'], 'broadcast_create', 'broadcast_log', $broadcastId, ['audience' => $audience, 'scheduled_at' => $scheduledAt]);

    if ($scheduledAt === null) {
        send_broadcast($broadcastId);
        redirect_flash('broadcast.php', 'Bildiriş göndərildi.');
    }

    redirect_flash('broadcast.php', 'Bildiriş planlaşdırıldı.');
}

$history = $db->query('SELECT * FROM broadcast_log ORDER BY id DESC LIMIT 30')->fetchAll();

admin_header('Bildiriş göndər', $session);
?>
<div class="card">
  <h2>Yeni bildiriş</h2>
  <form method="post">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Başlıq</label><input class="input" name="title" maxlength="160" required></div>
    <div class="field"><label>Mətn</label><textarea name="body" maxlength="255" rows="3" required></textarea></div>
    <div class="form-row">
      <div class="field"><label>Auditoriya</label>
        <select name="audience">
          <?php foreach ($audiences as $key => $label): ?><option value="<?= h($key) ?>"><?= h($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Planlaşdırılmış vaxt (boş = dərhal)</label><input class="input" type="datetime-local" name="scheduled_at"></div>
    </div>
    <button class="btn btn-primary" type="submit">Göndər</button>
  </form>
</div>

<div class="card">
  <h2>Tarixçə</h2>
  <?php if ($history === []): ?><div class="empty-state">Hələ bildiriş yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Başlıq</th><th>Auditoriya</th><th>Planlaşdırılıb</th><th>Göndərilib</th><th>Uğurlu/Uğursuz</th></tr></thead>
    <tbody>
      <?php foreach ($history as $b): ?>
      <tr>
        <td><?= h($b['title']) ?></td>
        <td><?= h($audiences[$b['audience']] ?? $b['audience']) ?></td>
        <td><?= h($b['scheduled_at'] ?? '—') ?></td>
        <td><?= $b['sent_at'] ? h($b['sent_at']) : badge('Gözləyir', 'warning') ?></td>
        <td><?= $b['sent_at'] ? "{$b['success_count']} / {$b['fail_count']}" : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
