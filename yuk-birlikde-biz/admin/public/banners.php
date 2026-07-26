<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/image.php';

$session = require_admin();
$db = db();

$placements = ['home_top' => 'Ana səhifənin yuxarısı', 'feed' => 'Elan lentinin arası', 'profile' => 'Profil səhifəsi', 'subscription' => 'Abunə səhifəsi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? '')) ?: null;
        $link = trim((string) ($_POST['link'] ?? '')) ?: null;
        $placement = (string) ($_POST['placement'] ?? 'home_top');
        $startsAt = $_POST['starts_at'] ?: null;
        $endsAt = $_POST['ends_at'] ?: null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sort = (int) ($_POST['sort'] ?? 0);

        if ($title === '' || !array_key_exists($placement, $placements)) {
            redirect_flash('banners.php', 'Başlıq və yer sahələri tələb olunur.', 'error');
        }

        $storageDir = __DIR__ . '/../../storage/uploads/banners';
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagePath = 'banners/' . save_uploaded_banner($_FILES['image'], $storageDir);
            } catch (Throwable $e) {
                redirect_flash('banners.php', 'Şəkil yüklənə bilmədi: ' . $e->getMessage(), 'error');
            }
        }

        if ($action === 'create') {
            if ($imagePath === null) {
                redirect_flash('banners.php', 'Şəkil tələb olunur.', 'error');
            }
            $stmt = $db->prepare(
                'INSERT INTO banners (image_path, title, description, link, placement, starts_at, ends_at, is_active, sort)
                 VALUES (:image_path, :title, :description, :link, :placement, :starts_at, :ends_at, :is_active, :sort)'
            );
            $stmt->execute(['image_path' => $imagePath, 'title' => $title, 'description' => $description, 'link' => $link, 'placement' => $placement, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'is_active' => $isActive, 'sort' => $sort]);
            $bannerId = (int) $db->lastInsertId();
            audit_log((int) $session['admin_id'], 'banner_create', 'banners', $bannerId);
        } else {
            $bannerId = (int) ($_POST['id'] ?? 0);
            if ($imagePath !== null) {
                $db->prepare('UPDATE banners SET image_path = :image_path WHERE id = :id')->execute(['image_path' => $imagePath, 'id' => $bannerId]);
            }
            $db->prepare(
                'UPDATE banners SET title = :title, description = :description, link = :link, placement = :placement,
                 starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active, sort = :sort WHERE id = :id'
            )->execute(['title' => $title, 'description' => $description, 'link' => $link, 'placement' => $placement, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'is_active' => $isActive, 'sort' => $sort, 'id' => $bannerId]);
            audit_log((int) $session['admin_id'], 'banner_update', 'banners', $bannerId);
        }

        publish_event('system', 'banner.updated', ['placement' => $placement]);
        redirect_flash('banners.php', 'Banner yadda saxlanıldı.');
    } elseif ($action === 'delete') {
        $bannerId = (int) ($_POST['id'] ?? 0);
        $imagePath = db_scalar($db, 'SELECT image_path FROM banners WHERE id = :id', ['id' => $bannerId]);
        if ($imagePath) {
            @unlink(__DIR__ . '/../../storage/uploads/' . $imagePath);
        }
        $db->prepare('DELETE FROM banners WHERE id = :id')->execute(['id' => $bannerId]);
        audit_log((int) $session['admin_id'], 'banner_delete', 'banners', $bannerId);
        publish_event('system', 'banner.updated', []);
        redirect_flash('banners.php', 'Banner silindi.');
    }
}

$banners = $db->query('SELECT * FROM banners ORDER BY placement, sort')->fetchAll();

admin_header('Bannerlər', $session);
?>
<div class="card">
  <h2>Yeni banner</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">
    <?= admin_csrf_field($session) ?>
    <div class="form-row">
      <div class="field"><label>Başlıq</label><input class="input" name="title" required></div>
      <div class="field"><label>Yer</label>
        <select name="placement">
          <?php foreach ($placements as $key => $label): ?><option value="<?= h($key) ?>"><?= h($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Sıra</label><input class="input" type="number" name="sort" value="0"></div>
    </div>
    <div class="field"><label>Açıqlama (istəyə bağlı)</label><input class="input" name="description"></div>
    <div class="field"><label>Keçid (link, istəyə bağlı)</label><input class="input" name="link" placeholder="https://..."></div>
    <div class="form-row">
      <div class="field"><label>Başlama tarixi</label><input class="input" type="date" name="starts_at"></div>
      <div class="field"><label>Bitmə tarixi</label><input class="input" type="date" name="ends_at"></div>
    </div>
    <div class="field"><label>Şəkil</label><input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
    <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="is_active" checked style="width:auto;"> Aktiv</label></div>
    <button class="btn btn-primary" type="submit">Yarat</button>
  </form>
</div>

<div class="card">
  <h2>Mövcud bannerlər</h2>
  <?php if ($banners === []): ?><div class="empty-state">Banner yoxdur.</div><?php else: ?>
  <table>
    <thead><tr><th>Şəkil</th><th>Başlıq</th><th>Yer</th><th>Status</th><th>Klik</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($banners as $b): ?>
      <tr>
        <td><img class="thumb" src="/uploads/<?= h($b['image_path']) ?>" alt=""></td>
        <td><?= h($b['title']) ?></td>
        <td><?= h($placements[$b['placement']] ?? $b['placement']) ?></td>
        <td><?= badge($b['is_active'] ? 'Aktiv' : 'Passiv', $b['is_active'] ? 'success' : 'muted') ?></td>
        <td><?= (int) $b['click_count'] ?></td>
        <td>
          <button class="btn btn-sm" type="button" onclick="document.getElementById('edit-<?= (int) $b['id'] ?>').style.display='block';">Redaktə</button>
          <form method="post" style="display:inline;" onsubmit="return confirm('Banner silinsin?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <?= admin_csrf_field($session) ?>
            <button class="btn btn-sm btn-danger" type="submit">Sil</button>
          </form>
        </td>
      </tr>
      <tr id="edit-<?= (int) $b['id'] ?>" style="display:none;"><td colspan="6">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
          <?= admin_csrf_field($session) ?>
          <div class="form-row">
            <div class="field"><label>Başlıq</label><input class="input" name="title" value="<?= h($b['title']) ?>" required></div>
            <div class="field"><label>Yer</label>
              <select name="placement">
                <?php foreach ($placements as $key => $label): ?><option value="<?= h($key) ?>" <?= $b['placement'] === $key ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field"><label>Sıra</label><input class="input" type="number" name="sort" value="<?= (int) $b['sort'] ?>"></div>
          </div>
          <div class="field"><label>Açıqlama</label><input class="input" name="description" value="<?= h($b['description']) ?>"></div>
          <div class="field"><label>Keçid</label><input class="input" name="link" value="<?= h($b['link']) ?>"></div>
          <div class="form-row">
            <div class="field"><label>Başlama</label><input class="input" type="date" name="starts_at" value="<?= h($b['starts_at'] ? substr($b['starts_at'], 0, 10) : '') ?>"></div>
            <div class="field"><label>Bitmə</label><input class="input" type="date" name="ends_at" value="<?= h($b['ends_at'] ? substr($b['ends_at'], 0, 10) : '') ?>"></div>
          </div>
          <div class="field"><label>Yeni şəkil (istəyə bağlı)</label><input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
          <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:400;"><input type="checkbox" name="is_active" <?= $b['is_active'] ? 'checked' : '' ?> style="width:auto;"> Aktiv</label></div>
          <button class="btn btn-primary" type="submit">Yadda saxla</button>
        </form>
      </td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php
admin_footer();
