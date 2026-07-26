<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/backup.php';

$session = require_admin();
$config = require __DIR__ . '/../../app/config.php';
$backupDir = __DIR__ . '/../../storage/backups';
$storageRoot = __DIR__ . '/../../storage';

function safe_backup_filename(string $backupDir, string $name): ?string
{
    $name = basename($name);
    if (!preg_match('/^backup_[0-9_-]+\.tar\.gz$/', $name)) {
        return null;
    }
    $path = $backupDir . '/' . $name;
    return is_file($path) ? $path : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        try {
            $name = create_backup($config['db'], $backupDir, $storageRoot . '/uploads');
            audit_log((int) $session['admin_id'], 'backup_create', 'backup', null, ['file' => $name]);
            redirect_flash('backup.php', 'Backup yaradıldı: ' . $name);
        } catch (Throwable $e) {
            log_error('backup', $e->getMessage());
            redirect_flash('backup.php', 'Backup yaradıla bilmədi: ' . $e->getMessage(), 'error');
        }
    } elseif ($action === 'restore') {
        $file = safe_backup_filename($backupDir, (string) ($_POST['file'] ?? ''));
        $confirmText = trim((string) ($_POST['confirm_text'] ?? ''));
        if ($file === null) {
            redirect_flash('backup.php', 'Fayl tapılmadı.', 'error');
        }
        if ($confirmText !== 'BƏRPA ET' || !isset($_POST['confirm_check'])) {
            redirect_flash('backup.php', 'Təsdiqləmə düzgün deyil — "BƏRPA ET" yazın və checkboxu işarələyin.', 'error');
        }
        try {
            restore_backup($file, $config['db'], $storageRoot);
            audit_log((int) $session['admin_id'], 'backup_restore', 'backup', null, ['file' => basename($file)]);
            redirect_flash('backup.php', 'Bərpa tamamlandı.');
        } catch (Throwable $e) {
            log_error('backup_restore', $e->getMessage());
            redirect_flash('backup.php', 'Bərpa uğursuz oldu: ' . $e->getMessage(), 'error');
        }
    }
}

if (isset($_GET['download'])) {
    $file = safe_backup_filename($backupDir, (string) $_GET['download']);
    if ($file === null) {
        redirect_flash('backup.php', 'Fayl tapılmadı.', 'error');
    }
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$files = glob($backupDir . '/backup_*.tar.gz') ?: [];
rsort($files);

admin_header('Backup', $session);
?>
<div class="card">
  <h2>Yeni backup</h2>
  <form method="post" onsubmit="this.querySelector('button').disabled=true;">
    <input type="hidden" name="action" value="create">
    <?= admin_csrf_field($session) ?>
    <p class="small-text" style="color:var(--text-muted);">Verilənlər bazası dump-ı və uploads qovluğu tar.gz arxivinə yığılır. Bir neçə saniyə çəkə bilər.</p>
    <button class="btn btn-primary" type="submit">Backup yarat</button>
  </form>
</div>

<div class="card">
  <h2>Mövcud backup-lar</h2>
  <?php if ($files === []): ?>
    <div class="empty-state">Hələ backup yoxdur.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Fayl</th><th>Ölçü</th><th>Tarix</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($files as $f): $name = basename($f); ?>
      <tr>
        <td><?= h($name) ?></td>
        <td><?= round(filesize($f) / 1048576, 2) ?> MB</td>
        <td><?= h(date('Y-m-d H:i', filemtime($f))) ?></td>
        <td>
          <a class="btn btn-sm" href="backup.php?download=<?= urlencode($name) ?>">Yüklə</a>
          <button class="btn btn-sm btn-danger" type="button" onclick="document.getElementById('restore-<?= h(md5($name)) ?>').style.display='block';">Bərpa et</button>
        </td>
      </tr>
      <tr id="restore-<?= h(md5($name)) ?>" style="display:none;"><td colspan="4">
        <form method="post" onsubmit="return confirm('Diqqət: bu əməliyyat mövcud verilənləri ARXİV MƏZMUNU ilə əvəz edəcək. Davam edilsin?');">
          <input type="hidden" name="action" value="restore">
          <input type="hidden" name="file" value="<?= h($name) ?>">
          <?= admin_csrf_field($session) ?>
          <p class="small-text" style="color:var(--error);">Bu geri qaytarıla bilməz. Davam etmək üçün "BƏRPA ET" yazın və təsdiqləyin.</p>
          <div class="field"><input class="input" name="confirm_text" placeholder="BƏRPA ET"></div>
          <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="confirm_check" style="width:auto;"> Nəticələri anlayıram, davam etmək istəyirəm</label></div>
          <button class="btn btn-danger" type="submit">Bərpa et</button>
        </form>
      </td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
