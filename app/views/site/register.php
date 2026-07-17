<?php
/** @var string|null $role */
/** @var array $vehicleTypes */
/** @var array $errors */
/** @var array $old */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="container">
<?php if ($role === null): ?>
  <h1><?= e(t('auth.choose_role')) ?></h1>
  <div class="role-cards" style="margin-top:20px">
    <a class="role-card" href="/qeydiyyat?rol=musteri">
      <div class="icon">📦</div>
      <h3><?= e(t('auth.role_customer')) ?></h3>
      <p class="text-soft"><?= e(t('auth.role_customer_desc')) ?></p>
    </a>
    <a class="role-card" href="/qeydiyyat?rol=surucu">
      <div class="icon">🚚</div>
      <h3><?= e(t('auth.role_driver')) ?></h3>
      <p class="text-soft"><?= e(t('auth.role_driver_desc')) ?></p>
    </a>
  </div>

<?php else: ?>
  <h1><?= e(t($role === 'surucu' ? 'auth.role_driver' : 'auth.role_customer')) ?></h1>

  <form method="post" action="/qeydiyyat" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="rol" value="<?= e($role) ?>">

    <div class="field">
      <label><?= e(t('common.full_name')) ?></label>
      <input type="text" name="full_name" value="<?= e($old['full_name'] ?? '') ?>" required>
      <?php if (isset($errors['full_name'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['full_name'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('common.phone')) ?></label>
      <input type="tel" name="phone" placeholder="<?= e(t('auth.phone_placeholder')) ?>" value="<?= e($old['phone'] ?? '') ?>" required>
      <?php if (isset($errors['phone'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['phone'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('common.password')) ?></label>
      <input type="password" name="password" required minlength="6">
      <?php if (isset($errors['password'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['password'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('auth.confirm_password')) ?></label>
      <input type="password" name="password_confirm" required minlength="6">
      <?php if (isset($errors['password_confirm'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['password_confirm'])) ?></p><?php endif; ?>
    </div>

    <?php if ($role === 'surucu'): ?>
    <div class="field">
      <label for="vehicle_type_id"><?= e(t('auth.vehicle_type')) ?></label>
      <select id="vehicle_type_id" name="vehicle_type_id" required>
        <option value="">—</option>
        <?php foreach ($vehicleTypes as $vt): ?>
          <option value="<?= (int) $vt['id'] ?>" <?= (int) ($old['vehicle_type_id'] ?? 0) === (int) $vt['id'] ? 'selected' : '' ?>>
            <?= e(Lang::field($vt, 'name')) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['vehicle_type_id'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['vehicle_type_id'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('auth.vehicle_note')) ?></label>
      <input type="text" name="vehicle_note" value="<?= e($old['vehicle_note'] ?? '') ?>">
    </div>

    <div class="field">
      <label><?= e(t('auth.vehicle_photo')) ?></label>
      <input type="file" name="vehicle_photos[]" accept="image/*" multiple required>
      <?php if (isset($errors['vehicle_photo'])): ?><p class="text-soft" style="color:var(--danger)"><?= e(t($errors['vehicle_photo'])) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-amber btn-block"><?= e(t('nav.register')) ?></button>
  </form>

  <p class="text-soft" style="text-align:center;margin-top:16px">
    <?= e(t('auth.has_account')) ?> <a href="/giris" class="link-amber"><?= e(t('nav.login')) ?></a>
  </p>
<?php endif; ?>
</div>
