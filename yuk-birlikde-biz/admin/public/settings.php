<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        $fields = [
            'reg_customer_enabled' => isset($_POST['reg_customer_enabled']) ? '1' : '0',
            'reg_driver_enabled' => isset($_POST['reg_driver_enabled']) ? '1' : '0',
            'pin_length' => (string) (((int) ($_POST['pin_length'] ?? 4)) === 6 ? 6 : 4),
            'order_ttl_hours' => (string) max(1, (int) ($_POST['order_ttl_hours'] ?? 72)),
            'reminder_intervals' => preg_replace('/[^0-9,]/', '', (string) ($_POST['reminder_intervals'] ?? '15,30,60,1440')),
            'session_days' => (string) max(1, (int) ($_POST['session_days'] ?? 90)),
            'notifications_enabled' => isset($_POST['notifications_enabled']) ? '1' : '0',
            'pwa_prompt_enabled' => isset($_POST['pwa_prompt_enabled']) ? '1' : '0',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        ];
        foreach ($fields as $key => $value) {
            settings_set($key, $value);
        }
        audit_log((int) $session['admin_id'], 'update_settings', 'settings', null, $fields);
        redirect_flash('settings.php', 'Parametrlər yeniləndi.');
    }

    $entityTables = ['operators' => ['col' => 'prefix'], 'vehicles' => ['col' => 'name'], 'cargo_types' => ['col' => 'name']];

    if (str_starts_with($action, 'add_') && isset($entityTables[substr($action, 4)])) {
        $entity = substr($action, 4);
        $col = $entityTables[$entity]['col'];
        $value = trim((string) ($_POST['value'] ?? ''));
        if ($value !== '') {
            $db->prepare("INSERT INTO {$entity} ({$col}, is_active, sort) VALUES (:v, 1, 0)")->execute(['v' => $value]);
            audit_log((int) $session['admin_id'], 'add_reference', $entity, (int) $db->lastInsertId(), ['value' => $value]);
        }
        redirect_flash('settings.php#' . $entity, 'Əlavə edildi.');
    }

    if ($action === 'add_vehicle_size') {
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $dimensions = trim((string) ($_POST['dimensions'] ?? ''));
        if ($code !== '') {
            $db->prepare('INSERT INTO vehicle_sizes (code, dimensions, is_active, sort) VALUES (:c, :d, 1, 0)')->execute(['c' => $code, 'd' => $dimensions]);
            audit_log((int) $session['admin_id'], 'add_reference', 'vehicle_sizes', (int) $db->lastInsertId());
        }
        redirect_flash('settings.php#vehicle_sizes', 'Əlavə edildi.');
    }

    if (in_array($action, ['toggle', 'delete'], true)) {
        $entity = (string) ($_POST['entity'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $allowed = ['operators', 'vehicles', 'cargo_types', 'vehicle_sizes'];
        if (in_array($entity, $allowed, true)) {
            if ($action === 'toggle') {
                $db->prepare("UPDATE {$entity} SET is_active = 1 - is_active WHERE id = :id")->execute(['id' => $id]);
            } else {
                $db->prepare("DELETE FROM {$entity} WHERE id = :id")->execute(['id' => $id]);
            }
            audit_log((int) $session['admin_id'], $action . '_reference', $entity, $id);
        }
        redirect_flash('settings.php#' . $entity, 'Yadda saxlanıldı.');
    }
}

$s = static fn (string $k, string $d = '') => settings_get($k, $d);
$operators = $db->query('SELECT * FROM operators ORDER BY sort, id')->fetchAll();
$vehicles = $db->query('SELECT * FROM vehicles ORDER BY sort, id')->fetchAll();
$vehicleSizes = $db->query('SELECT * FROM vehicle_sizes ORDER BY sort, id')->fetchAll();
$cargoTypes = $db->query('SELECT * FROM cargo_types ORDER BY sort, id')->fetchAll();

admin_header('Sistem parametrləri', $session);

function ref_table(string $entity, string $label, array $rows, string $session_csrf_html, string $colName = 'name'): void
{
    ?>
    <div class="card" id="<?= h($entity) ?>">
      <h2><?= h($label) ?></h2>
      <table>
        <thead><tr><th><?= h($label) ?></th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r[$colName]) ?></td>
            <td><?= badge($r['is_active'] ? 'Aktiv' : 'Passiv', $r['is_active'] ? 'success' : 'muted') ?></td>
            <td>
              <form method="post" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="entity" value="<?= h($entity) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><?= $session_csrf_html ?><button class="btn btn-sm" type="submit"><?= $r['is_active'] ? 'Passiv et' : 'Aktiv et' ?></button></form>
              <form method="post" style="display:inline;" onsubmit="return confirm('Silinsin?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="entity" value="<?= h($entity) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><?= $session_csrf_html ?><button class="btn btn-sm btn-danger" type="submit">Sil</button></form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post" class="form-row" style="margin-top:12px;align-items:flex-end;">
        <input type="hidden" name="action" value="add_<?= h($entity) ?>">
        <?= $session_csrf_html ?>
        <div class="field"><input class="input" name="value" placeholder="Yeni <?= h($label) ?>"></div>
        <div class="field"><button class="btn" type="submit">Əlavə et</button></div>
      </form>
    </div>
    <?php
}

$csrfHtml = admin_csrf_field($session);
?>
<div class="card">
  <h2>Qeydiyyat və ümumi parametrlər</h2>
  <form method="post">
    <input type="hidden" name="action" value="save_settings">
    <?= $csrfHtml ?>
    <div class="form-row">
      <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="reg_customer_enabled" <?= $s('reg_customer_enabled', '1') === '1' ? 'checked' : '' ?> style="width:auto;"> Müştəri qeydiyyatı aktiv</label></div>
      <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="reg_driver_enabled" <?= $s('reg_driver_enabled', '1') === '1' ? 'checked' : '' ?> style="width:auto;"> Sürücü qeydiyyatı aktiv</label></div>
    </div>
    <div class="form-row">
      <div class="field"><label>PIN uzunluğu</label>
        <select name="pin_length">
          <option value="4" <?= $s('pin_length', '4') === '4' ? 'selected' : '' ?>>4 rəqəm</option>
          <option value="6" <?= $s('pin_length', '4') === '6' ? 'selected' : '' ?>>6 rəqəm</option>
        </select>
      </div>
      <div class="field"><label>Elan müddəti (saat)</label><input class="input" type="number" name="order_ttl_hours" value="<?= h($s('order_ttl_hours', '72')) ?>"></div>
      <div class="field"><label>Sessiya müddəti (gün)</label><input class="input" type="number" name="session_days" value="<?= h($s('session_days', '90')) ?>"></div>
    </div>
    <div class="field"><label>Xatırlatma intervalları (dəqiqə, vergüllə)</label><input class="input" name="reminder_intervals" value="<?= h($s('reminder_intervals', '15,30,60,1440')) ?>"></div>
    <div class="form-row">
      <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="notifications_enabled" <?= $s('notifications_enabled', '1') === '1' ? 'checked' : '' ?> style="width:auto;"> Bildiriş sistemi aktiv</label></div>
      <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="pwa_prompt_enabled" <?= $s('pwa_prompt_enabled', '1') === '1' ? 'checked' : '' ?> style="width:auto;"> PWA quraşdırma bildirişi aktiv</label></div>
      <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;color:var(--error);"><input type="checkbox" name="maintenance_mode" <?= $s('maintenance_mode', '0') === '1' ? 'checked' : '' ?> style="width:auto;"> Texniki baxım rejimi</label></div>
    </div>
    <button class="btn btn-primary" type="submit">Yadda saxla</button>
  </form>
</div>

<?php
ref_table('operators', 'Operatorlar', $operators, $csrfHtml, 'prefix');
ref_table('vehicles', 'Avtomobillər', $vehicles, $csrfHtml, 'name');
ref_table('cargo_types', 'Yük növləri', $cargoTypes, $csrfHtml, 'name');
?>

<div class="card" id="vehicle_sizes">
  <h2>Avtomobil ölçüləri</h2>
  <table>
    <thead><tr><th>Kod</th><th>Ölçülər</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($vehicleSizes as $vs): ?>
      <tr>
        <td><?= h($vs['code']) ?></td>
        <td><?= h($vs['dimensions']) ?></td>
        <td><?= badge($vs['is_active'] ? 'Aktiv' : 'Passiv', $vs['is_active'] ? 'success' : 'muted') ?></td>
        <td>
          <form method="post" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="entity" value="vehicle_sizes"><input type="hidden" name="id" value="<?= (int) $vs['id'] ?>"><?= $csrfHtml ?><button class="btn btn-sm" type="submit"><?= $vs['is_active'] ? 'Passiv et' : 'Aktiv et' ?></button></form>
          <form method="post" style="display:inline;" onsubmit="return confirm('Silinsin?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="entity" value="vehicle_sizes"><input type="hidden" name="id" value="<?= (int) $vs['id'] ?>"><?= $csrfHtml ?><button class="btn btn-sm btn-danger" type="submit">Sil</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <form method="post" class="form-row" style="margin-top:12px;align-items:flex-end;">
    <input type="hidden" name="action" value="add_vehicle_size">
    <?= $csrfHtml ?>
    <div class="field"><input class="input" name="code" placeholder="Kod (məs: M)" maxlength="2"></div>
    <div class="field"><input class="input" name="dimensions" placeholder="Ölçülər (məs: 3x2x2m)"></div>
    <div class="field"><button class="btn" type="submit">Əlavə et</button></div>
  </form>
</div>
<?php
admin_footer();
