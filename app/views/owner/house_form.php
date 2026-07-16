<?php
/** @var int $step */
/** @var array<string,mixed>|null $house */
/** @var string[] $errors */
/** @var array<string,mixed> $old */
/** @var array<int,array<string,mixed>> $regions */
/** @var array<int,array<string,mixed>> $amenities */
/** @var int[] $selectedAmenityIds */
/** @var array<int,array<string,mixed>> $photos */
/** @var bool $saved */

$isCreate = $house === null;
$val = static function (string $key, string $default = '') use ($old, $house): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($house !== null && array_key_exists($key, $house) && $house[$key] !== null) {
        return (string) $house[$key];
    }
    return $default;
};
$actionUrl = $isCreate ? '/sahib/ev/yeni' : '/sahib/ev/' . (int) $house['id'] . '/redakte';
$lang = Lang::current();
?>
<section class="wizard">
    <h1><?= View::e(Lang::t('owner.house_form_title')) ?></h1>

    <nav class="wizard__steps">
        <?php for ($i = 1; $i <= 4; $i++):
            $reachable = $isCreate ? $i === 1 : true;
            $stepUrl = $isCreate ? '#' : '/sahib/ev/' . (int) $house['id'] . '/redakte?addim=' . $i;
        ?>
            <?php if ($reachable): ?>
                <a class="wizard__step<?= $i === $step ? ' is-active' : '' ?>" href="<?= View::e($stepUrl) ?>"><?= $i ?></a>
            <?php else: ?>
                <span class="wizard__step wizard__step--disabled"><?= $i ?></span>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>

    <?php if ($errors !== []): ?>
        <ul class="form-errors">
            <?php foreach ($errors as $err): ?><li><?= View::e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($saved): ?>
        <p class="notice notice--success"><?= View::e(Lang::t('owner.saved_notice')) ?></p>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="post" action="<?= View::e($actionUrl) ?>" class="wizard-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="addim" value="1">
            <h2><?= View::e(Lang::t('owner.step1_title')) ?></h2>

            <label><?= View::e(Lang::t('owner.field_title')) ?>
                <input type="text" name="title" required minlength="3" value="<?= View::e($val('title')) ?>">
            </label>
            <label><?= View::e(Lang::t('owner.field_region')) ?>
                <select name="region_id" required>
                    <option value=""></option>
                    <?php foreach ($regions as $r): $sel = $val('region_id') === (string) $r['id']; ?>
                        <option value="<?= (int) $r['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= View::e($r['name_az']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= View::e(Lang::t('owner.field_village')) ?>
                <input type="text" name="village" value="<?= View::e($val('village')) ?>">
            </label>
            <label><?= View::e(Lang::t('owner.field_description')) ?>
                <textarea name="description" required minlength="20" rows="5"><?= View::e($val('description')) ?></textarea>
            </label>

            <details class="wizard-optional-langs">
                <summary><?= View::e(Lang::t('owner.field_title_optional_langs')) ?> / <?= View::e(Lang::t('owner.field_description_optional_langs')) ?></summary>
                <label>RU <input type="text" name="title_ru" value="<?= View::e($val('title_ru')) ?>"></label>
                <label>EN <input type="text" name="title_en" value="<?= View::e($val('title_en')) ?>"></label>
                <label>RU <textarea name="description_ru" rows="3"><?= View::e($val('description_ru')) ?></textarea></label>
                <label>EN <textarea name="description_en" rows="3"><?= View::e($val('description_en')) ?></textarea></label>
            </details>

            <div class="wizard-form__row">
                <label><?= View::e(Lang::t('owner.field_rooms')) ?>
                    <input type="number" name="rooms" min="1" max="20" required value="<?= View::e($val('rooms', '1')) ?>">
                </label>
                <label><?= View::e(Lang::t('owner.field_capacity')) ?>
                    <input type="number" name="capacity" min="1" max="50" required value="<?= View::e($val('capacity', '2')) ?>">
                </label>
            </div>

            <button type="submit" class="btn btn--primary btn--large"><?= View::e(Lang::t('owner.action_next')) ?></button>
        </form>

    <?php elseif ($step === 2): ?>
        <form method="post" action="<?= View::e($actionUrl) ?>" class="wizard-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="addim" value="2">
            <h2><?= View::e(Lang::t('owner.step2_title')) ?></h2>

            <div class="wizard-form__row">
                <label><?= View::e(Lang::t('owner.field_price_night')) ?>
                    <input type="number" step="0.01" min="0.01" name="price_night" required value="<?= View::e($val('price_night')) ?>">
                </label>
                <label><?= View::e(Lang::t('owner.field_price_weekend')) ?>
                    <input type="number" step="0.01" min="0.01" name="price_weekend" value="<?= View::e($val('price_weekend')) ?>">
                </label>
            </div>
            <label><?= View::e(Lang::t('owner.field_whatsapp_phone')) ?>
                <input type="tel" name="whatsapp_phone" required value="<?= View::e($val('whatsapp_phone')) ?>">
            </label>
            <div class="wizard-form__row">
                <label><?= View::e(Lang::t('owner.field_map_lat')) ?>
                    <input type="text" name="map_lat" value="<?= View::e($val('map_lat')) ?>">
                </label>
                <label><?= View::e(Lang::t('owner.field_map_lng')) ?>
                    <input type="text" name="map_lng" value="<?= View::e($val('map_lng')) ?>">
                </label>
            </div>

            <button type="submit" class="btn btn--primary btn--large"><?= View::e(Lang::t($isCreate ? 'owner.action_next' : 'owner.action_save')) ?></button>
        </form>

    <?php elseif ($step === 3): ?>
        <form method="post" action="<?= View::e($actionUrl) ?>" class="wizard-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="addim" value="3">
            <h2><?= View::e(Lang::t('owner.step3_title')) ?></h2>

            <div class="filters__amenities wizard-amenities">
                <?php foreach ($amenities as $a):
                    $checked = in_array((int) $a['id'], $selectedAmenityIds, true);
                    $name = match ($lang) { 'ru' => $a['name_ru'], 'en' => $a['name_en'], default => $a['name_az'] };
                ?>
                    <label class="chip-checkbox">
                        <input type="checkbox" name="amenities[]" value="<?= (int) $a['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                        <span><?= View::e($a['icon']) ?> <?= View::e($name) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--large"><?= View::e(Lang::t($isCreate ? 'owner.action_next' : 'owner.action_save')) ?></button>
        </form>

    <?php elseif ($step === 4 && $house !== null): ?>
        <div class="wizard-photos">
            <h2><?= View::e(Lang::t('owner.step4_title')) ?></h2>
            <p class="wizard-photos__note"><?= View::e(Lang::t('owner.photos_min_note')) ?></p>

            <div class="photo-grid" id="photo-grid"
                 data-upload-url="/sahib/ev/<?= (int) $house['id'] ?>/fotolar"
                 data-csrf="<?= View::e(Csrf::token()) ?>"
                 data-label-cover="<?= View::e(Lang::t('owner.photos_make_cover')) ?>"
                 data-label-delete="<?= View::e(Lang::t('owner.photos_delete')) ?>"
                 data-label-pending="<?= View::e(Lang::t('owner.photos_pending_note')) ?>">
                <?php foreach ($photos as $p): ?>
                    <div class="photo-tile" data-photo-id="<?= (int) $p['id'] ?>">
                        <?php if ($p['is_video']): ?>
                            <video src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" muted></video>
                        <?php else: ?>
                            <img src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" alt="">
                        <?php endif; ?>
                        <?php if ($p['is_cover']): ?><span class="badge badge--verified photo-tile__cover-badge"><?= View::e(Lang::t('owner.photos_cover_label')) ?></span><?php endif; ?>
                        <?php if (!$p['is_approved']): ?><span class="badge badge--warn photo-tile__pending-badge"><?= View::e(Lang::t('owner.photos_pending_note')) ?></span><?php endif; ?>
                        <div class="photo-tile__actions">
                            <?php if (!$p['is_video']): ?>
                                <button type="button" class="photo-set-cover" data-photo-id="<?= (int) $p['id'] ?>"><?= View::e(Lang::t('owner.photos_make_cover')) ?></button>
                            <?php endif; ?>
                            <button type="button" class="photo-delete" data-photo-id="<?= (int) $p['id'] ?>"><?= View::e(Lang::t('owner.photos_delete')) ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <label class="btn photo-upload-btn">
                <?= View::e(Lang::t('owner.photos_upload_button')) ?>
                <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp,video/mp4" hidden>
            </label>

            <form method="post" action="/sahib/ev/<?= (int) $house['id'] ?>/gonder" class="wizard-photos__submit">
                <?= Csrf::field() ?>
                <a class="btn" href="/sahib/panel"><?= View::e(Lang::t('owner.action_back_to_panel')) ?></a>
                <?php if ($house['status'] !== 'approved'): ?>
                    <button type="submit" class="btn btn--primary btn--large" id="submit-for-approval-btn" <?= count(array_filter($photos, static fn ($p) => !$p['is_video'])) < 4 ? 'disabled' : '' ?>>
                        <?= View::e(Lang::t('owner.action_submit_for_approval')) ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</section>
