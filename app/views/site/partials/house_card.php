<?php
/** @var array<string,mixed> $house */
/** @var array<string,mixed> $filters */
$lang = Lang::current();
$cardTitle = match ($lang) {
    'ru' => $house['title_ru'] ?: $house['title'],
    'en' => $house['title_en'] ?: $house['title'],
    default => $house['title'],
};
$cardRegion = match ($lang) {
    'ru' => $house['region_name_ru'],
    'en' => $house['region_name_en'],
    default => $house['region_name_az'],
};

$query = [];
if (!empty($filters['checkin'])) {
    $query['checkin'] = $filters['checkin'];
}
if (!empty($filters['checkout'])) {
    $query['checkout'] = $filters['checkout'];
}
if (!empty($filters['guests'])) {
    $query['guests'] = $filters['guests'];
}
$href = '/ev/' . rawurlencode((string) $house['slug']) . ($query !== [] ? '?' . http_build_query($query) : '');
$cover = $house['cover_photo'] ?? null;
?>
<a class="house-card<?= !empty($house['maybe_full']) ? ' is-maybe-full' : '' ?>" href="<?= View::e($href) ?>">
    <div class="house-card__photo">
        <?php if ($cover): ?>
            <img src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e((string) $cover) ?>" alt="<?= View::e($cardTitle) ?>" loading="lazy">
        <?php else: ?>
            <div class="house-card__placeholder" aria-hidden="true">🏡</div>
        <?php endif; ?>
        <?php if (!empty($house['maybe_full'])): ?>
            <span class="badge badge--warn"><?= View::e(Lang::t('listing.badge_maybe_full')) ?></span>
        <?php endif; ?>
    </div>
    <div class="house-card__body">
        <h3><?= View::e($cardTitle) ?></h3>
        <p class="house-card__meta"><?= View::e($cardRegion) ?><?= $house['village'] ? ' · ' . View::e((string) $house['village']) : '' ?></p>
        <p class="house-card__price"><?= number_format((float) $house['price_night'], 0) ?> AZN <span>/ <?= View::e(Lang::t('listing.price_from')) ?></span></p>
        <p class="house-card__stats"><?= (int) $house['capacity'] ?> <?= View::e(Lang::t('listing.capacity_short')) ?> · <?= (int) $house['rooms'] ?> <?= View::e(Lang::t('house.rooms')) ?></p>
    </div>
</a>
