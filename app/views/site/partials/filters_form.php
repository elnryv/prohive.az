<?php
/** @var array<string,mixed> $filters */
/** @var array<int,array<string,mixed>> $amenities */
/** @var string $action */
/** @var array<int,array<string,mixed>>|null $regions */
/** @var array<string,mixed>|null $selectedRegion */
$showRegionSelect = isset($regions);
$lang = Lang::current();
$amenityIds = $filters['amenity_ids'] ?? [];
?>
<form class="filters" method="get" action="<?= View::e($action) ?>">
    <?php if ($showRegionSelect): ?>
        <div class="filters__field">
            <label for="f-region"><?= View::e(Lang::t('home.search_region')) ?></label>
            <select id="f-region" name="region">
                <option value="hamisi"><?= View::e(Lang::t('home.search_region_all')) ?></option>
                <?php foreach ($regions as $r): $sel = ($selectedRegion['id'] ?? null) === $r['id']; ?>
                    <option value="<?= View::e($r['slug']) ?>" <?= $sel ? 'selected' : '' ?>>
                        <?= View::e($r['name_az']) ?> (<?= (int) $r['house_count'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="filters__field filters__field--range">
        <label><?= View::e(Lang::t('listing.filter_price')) ?> (AZN)</label>
        <div class="filters__range-inputs">
            <input type="number" name="price_min" min="0" placeholder="<?= View::e(Lang::t('listing.filter_price_min')) ?>" value="<?= View::e((string) ($filters['price_min'] ?? '')) ?>">
            <span>–</span>
            <input type="number" name="price_max" min="0" placeholder="<?= View::e(Lang::t('listing.filter_price_max')) ?>" value="<?= View::e((string) ($filters['price_max'] ?? '')) ?>">
        </div>
    </div>

    <div class="filters__field">
        <label for="f-guests"><?= View::e(Lang::t('listing.filter_capacity')) ?></label>
        <input type="number" id="f-guests" name="guests" min="1" value="<?= View::e((string) ($filters['guests'] ?? '')) ?>">
    </div>

    <div class="filters__field filters__field--dates">
        <label><?= View::e(Lang::t('listing.filter_dates')) ?></label>
        <div class="filters__range-inputs">
            <input type="date" name="checkin" value="<?= View::e((string) ($filters['checkin'] ?? '')) ?>" aria-label="<?= View::e(Lang::t('listing.filter_checkin')) ?>">
            <span>–</span>
            <input type="date" name="checkout" value="<?= View::e((string) ($filters['checkout'] ?? '')) ?>" aria-label="<?= View::e(Lang::t('listing.filter_checkout')) ?>">
        </div>
    </div>

    <fieldset class="filters__amenities">
        <legend><?= View::e(Lang::t('listing.filter_amenities')) ?></legend>
        <?php foreach ($amenities as $a):
            $checked = in_array((int) $a['id'], $amenityIds, true);
            $name = match ($lang) {
                'ru' => $a['name_ru'],
                'en' => $a['name_en'],
                default => $a['name_az'],
            };
        ?>
            <label class="chip-checkbox">
                <input type="checkbox" name="amenities[]" value="<?= (int) $a['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= View::e($a['icon']) ?> <?= View::e($name) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <div class="filters__field">
        <label for="f-sort"><?= View::e(Lang::t('listing.sort_label')) ?></label>
        <select id="f-sort" name="sort">
            <option value="default" <?= ($filters['sort'] ?? 'default') === 'default' ? 'selected' : '' ?>><?= View::e(Lang::t('listing.sort_default')) ?></option>
            <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>><?= View::e(Lang::t('listing.sort_price_asc')) ?></option>
            <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>><?= View::e(Lang::t('listing.sort_price_desc')) ?></option>
        </select>
    </div>

    <button type="submit" class="btn btn--primary"><?= View::e(Lang::t('listing.filter_apply')) ?></button>
</form>
