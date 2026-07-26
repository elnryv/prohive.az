<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$session = require_admin();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_validate($session);

    $mode = ($_POST['mode'] ?? 'free') === 'paid' ? 'paid' : 'free';
    $price = number_format((float) ($_POST['price'] ?? 0), 2, '.', '');
    $days = max(1, (int) ($_POST['days'] ?? 30));
    $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'AZN')));
    $activation = ($_POST['activation'] ?? 'immediate') === 'stack' ? 'stack' : 'immediate';

    $oldMode = settings_get('subscription_mode', 'free');

    settings_set('subscription_mode', $mode);
    settings_set('subscription_price', $price);
    settings_set('subscription_days', (string) $days);
    settings_set('subscription_currency', $currency);
    settings_set('subscription_activation', $activation);

    audit_log((int) $session['admin_id'], 'update_subscription_settings', 'settings', null, [
        'mode' => $mode, 'price' => $price, 'days' => $days, 'currency' => $currency, 'activation' => $activation,
    ]);

    if ($oldMode !== $mode) {
        publish_event('system', 'subscription.mode_changed', ['mode' => $mode]);
    }

    redirect_flash('subscription-settings.php', 'Abunə parametrləri yeniləndi.');
}

$mode = settings_get('subscription_mode', 'free');
$price = settings_get('subscription_price', '20');
$days = settings_get_int('subscription_days', 30);
$currency = settings_get('subscription_currency', 'AZN');
$activation = settings_get('subscription_activation', 'immediate');

admin_header('Abunə parametrləri', $session);
?>
<div class="card">
  <h2>Rejim və qiymət</h2>
  <form method="post">
    <?= admin_csrf_field($session) ?>
    <div class="form-row">
      <div class="field">
        <label>Rejim</label>
        <select name="mode">
          <option value="free" <?= $mode === 'free' ? 'selected' : '' ?>>Pulsuz</option>
          <option value="paid" <?= $mode === 'paid' ? 'selected' : '' ?>>Ödənişli</option>
        </select>
      </div>
      <div class="field"><label>Qiymət</label><input class="input" type="number" step="0.01" name="price" value="<?= h($price) ?>"></div>
      <div class="field"><label>Müddət (gün)</label><input class="input" type="number" name="days" value="<?= (int) $days ?>"></div>
      <div class="field"><label>Valyuta</label><input class="input" name="currency" value="<?= h($currency) ?>" maxlength="3"></div>
    </div>
    <div class="field">
      <label>Ödənişdən sonrakı aktivləşmə qaydası</label>
      <select name="activation">
        <option value="immediate" <?= $activation === 'immediate' ? 'selected' : '' ?>>Dərhal (mövcud abunə ləğv olunur)</option>
        <option value="stack" <?= $activation === 'stack' ? 'selected' : '' ?>>Mövcud abunənin üstünə əlavə et</option>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Yadda saxla</button>
  </form>
  <p class="small-text" style="color:var(--text-muted);margin-top:12px;">Dəyişikliklər dərhal qüvvəyə minir. Rejim dəyişəndə bütün aktiv istifadəçilərə SSE bildirişi gedir.</p>
</div>
<?php
admin_footer();
