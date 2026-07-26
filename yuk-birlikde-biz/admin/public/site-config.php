<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../../app/image.php';

$session = require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);

    $socialLinks = [
        'instagram' => trim((string) ($_POST['social_instagram'] ?? '')),
        'facebook' => trim((string) ($_POST['social_facebook'] ?? '')),
    ];

    settings_set('site_name', trim((string) ($_POST['site_name'] ?? 'Yük Birlikdə')));
    settings_set('whatsapp_number', trim((string) ($_POST['whatsapp_number'] ?? '')));
    settings_set('contact_phone', trim((string) ($_POST['contact_phone'] ?? '')));
    settings_set('contact_email', trim((string) ($_POST['contact_email'] ?? '')));
    settings_set('social_links', json_encode($socialLinks, JSON_UNESCAPED_UNICODE));
    settings_set('copyright', trim((string) ($_POST['copyright'] ?? '')));

    $storageDir = __DIR__ . '/../../storage/uploads/site';
    foreach (['logo' => 'logo_path', 'splash_logo' => 'splash_logo_path', 'favicon' => 'favicon_path'] as $field => $settingKey) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $filename = save_uploaded_site_asset($_FILES[$field], $storageDir);
                settings_set($settingKey, 'site/' . $filename);
            } catch (Throwable $e) {
                redirect_flash('site-config.php', "Fayl yüklənə bilmədi ({$field}): " . $e->getMessage(), 'error');
            }
        }
    }

    audit_log((int) $session['admin_id'], 'update_site_config', 'settings', null);
    redirect_flash('site-config.php', 'Konfiqurasiya yeniləndi.');
}

$s = static fn (string $k, string $d = '') => settings_get($k, $d);
$social = json_decode($s('social_links', '{}'), true) ?: [];

admin_header('Sayt konfiqurasiyası', $session);
?>
<div class="card">
  <form method="post" enctype="multipart/form-data">
    <?= admin_csrf_field($session) ?>
    <div class="field"><label>Sayt adı</label><input class="input" name="site_name" value="<?= h($s('site_name', 'Yük Birlikdə')) ?>"></div>
    <div class="form-row">
      <div class="field"><label>WhatsApp nömrəsi</label><input class="input" name="whatsapp_number" value="<?= h($s('whatsapp_number')) ?>"></div>
      <div class="field"><label>Əlaqə telefonu</label><input class="input" name="contact_phone" value="<?= h($s('contact_phone')) ?>"></div>
      <div class="field"><label>E-poçt</label><input class="input" type="email" name="contact_email" value="<?= h($s('contact_email')) ?>"></div>
    </div>
    <div class="form-row">
      <div class="field"><label>Instagram</label><input class="input" name="social_instagram" value="<?= h($social['instagram'] ?? '') ?>"></div>
      <div class="field"><label>Facebook</label><input class="input" name="social_facebook" value="<?= h($social['facebook'] ?? '') ?>"></div>
    </div>
    <div class="field"><label>Copyright</label><input class="input" name="copyright" value="<?= h($s('copyright')) ?>"></div>

    <div class="form-row">
      <div class="field">
        <label>Loqo</label>
        <?php if ($s('logo_path')): ?><div><img class="thumb" src="/uploads/<?= h($s('logo_path')) ?>"></div><?php endif; ?>
        <input class="input" type="file" name="logo" accept="image/*">
      </div>
      <div class="field">
        <label>Splash Screen loqosu</label>
        <?php if ($s('splash_logo_path')): ?><div><img class="thumb" src="/uploads/<?= h($s('splash_logo_path')) ?>"></div><?php endif; ?>
        <input class="input" type="file" name="splash_logo" accept="image/*">
      </div>
      <div class="field">
        <label>Favicon</label>
        <?php if ($s('favicon_path')): ?><div><img class="thumb" src="/uploads/<?= h($s('favicon_path')) ?>"></div><?php endif; ?>
        <input class="input" type="file" name="favicon" accept="image/*">
      </div>
    </div>

    <button class="btn btn-primary" type="submit">Yadda saxla</button>
  </form>
</div>
<?php
admin_footer();
