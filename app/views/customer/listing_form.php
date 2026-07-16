<?php
/** @var array $categories */
/** @var array $locations */
/** @var array $errors */
/** @var array $old */
use App\Core\Csrf;
use App\Core\Lang;

$bakuLocations = array_filter($locations, static fn ($l) => (int) $l['is_baku'] === 1);
$regionLocations = array_filter($locations, static fn ($l) => (int) $l['is_baku'] === 0);
?>
<div class="container">
  <h1><?= e(t('nav.new_listing')) ?></h1>
  <?php if (isset($errors['limit'])): ?>
    <div class="banner banner-error"><?= e(t($errors['limit'])) ?></div>
  <?php endif; ?>

  <form method="post" action="/musteri/elan/yeni" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>

    <div class="field">
      <label><?= e(t('listing.category')) ?></label>
      <div class="role-cards" style="grid-template-columns:repeat(2,1fr);display:grid;gap:8px">
        <?php foreach ($categories as $cat): ?>
          <label class="role-card" style="padding:12px;cursor:pointer">
            <input type="radio" name="category_id" value="<?= (int) $cat['id'] ?>" style="width:auto;min-height:auto"
              <?= (int) ($old['category_id'] ?? 0) === (int) $cat['id'] ? 'checked' : '' ?> required>
            <span style="font-size:20px"><?= e($cat['icon']) ?></span>
            <div style="font-size:13px;font-weight:600"><?= e(Lang::field($cat, 'name')) ?></div>
          </label>
        <?php endforeach; ?>
      </div>
      <?php if (isset($errors['category_id'])): ?><p style="color:var(--danger)"><?= e(t($errors['category_id'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('listing.from')) ?></label>
      <select name="from_location_id" required>
        <option value="">—</option>
        <optgroup label="Bakı">
          <?php foreach ($bakuLocations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>" <?= (int) ($old['from_location_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Bölgə">
          <?php foreach ($regionLocations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>" <?= (int) ($old['from_location_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </optgroup>
      </select>
      <input type="text" name="from_detail" placeholder="<?= e(t('listing.from_detail')) ?>" value="<?= e($old['from_detail'] ?? '') ?>" style="margin-top:8px">
      <?php if (isset($errors['from_location_id'])): ?><p style="color:var(--danger)"><?= e(t($errors['from_location_id'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('listing.to')) ?></label>
      <select name="to_location_id" required>
        <option value="">—</option>
        <optgroup label="Bakı">
          <?php foreach ($bakuLocations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>" <?= (int) ($old['to_location_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Bölgə">
          <?php foreach ($regionLocations as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>" <?= (int) ($old['to_location_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e(Lang::field($loc, 'name')) ?></option>
          <?php endforeach; ?>
        </optgroup>
      </select>
      <input type="text" name="to_detail" placeholder="<?= e(t('listing.to_detail')) ?>" value="<?= e($old['to_detail'] ?? '') ?>" style="margin-top:8px">
      <?php if (isset($errors['to_location_id'])): ?><p style="color:var(--danger)"><?= e(t($errors['to_location_id'])) ?></p><?php endif; ?>
    </div>
    <p class="text-soft"><?= e(t('listing.detail_warning')) ?></p>

    <div class="field">
      <label><?= e(t('listing.date')) ?></label>
      <div style="display:flex;gap:8px;margin-bottom:8px">
        <label class="chip" style="cursor:pointer"><input type="radio" name="date_mode" value="agreement" checked style="width:auto;min-height:auto"> <?= e(t('listing.date_agreement')) ?></label>
        <label class="chip" style="cursor:pointer"><input type="radio" name="date_mode" value="exact" style="width:auto;min-height:auto"> <?= e(t('listing.date_exact')) ?></label>
      </div>
      <input type="date" name="move_date" value="<?= e($old['move_date'] ?? '') ?>">
      <?php if (isset($errors['move_date'])): ?><p style="color:var(--danger)"><?= e(t($errors['move_date'])) ?></p><?php endif; ?>
      <label class="chip chip-urgent" style="cursor:pointer;margin-top:8px;display:inline-flex">
        <input type="checkbox" name="is_urgent" value="1" style="width:auto;min-height:auto" <?= !empty($old['is_urgent']) ? 'checked' : '' ?>>
        <?= e(t('listing.urgent')) ?>
      </label>
    </div>

    <div class="field">
      <label><?= e(t('listing.description')) ?></label>
      <textarea name="description" required><?= e($old['description'] ?? '') ?></textarea>
      <?php if (isset($errors['description'])): ?><p style="color:var(--danger)"><?= e(t($errors['description'])) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label><?= e(t('listing.photos')) ?></label>
      <input type="file" name="photos[]" accept="image/*" multiple>
      <?php if (isset($errors['photos'])): ?><p style="color:var(--danger)"><?= e(t($errors['photos'])) ?></p><?php endif; ?>
    </div>

    <p class="text-soft"><?= e(t('listing.no_price_note')) ?></p>
    <button type="submit" class="btn btn-amber btn-block"><?= e(t('listing.submit')) ?></button>
  </form>
</div>
