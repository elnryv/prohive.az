<?php
/** @var array<string,mixed> $region */
$lang = Lang::current();
$regionName = match ($lang) {
    'ru' => $region['name_ru'],
    'en' => $region['name_en'],
    default => $region['name_az'],
};
$regionTagline = match ($lang) {
    'ru' => $region['tagline_ru'],
    'en' => $region['tagline_en'],
    default => $region['tagline_az'],
};
?>
<section class="region-hero<?= $region['cover_img'] ? '' : ' region-hero--placeholder' ?>"
    <?= $region['cover_img'] ? 'style="background-image:url(\'' . View::e($region['cover_img']) . '\')"' : '' ?>>
    <div class="region-hero__overlay">
        <h1><?= View::e($regionName) ?></h1>
        <?php if ($regionTagline): ?><p><?= View::e($regionTagline) ?></p><?php endif; ?>
        <p class="region-hero__count"><?= View::e(Lang::tf('listing.house_count', $total)) ?></p>
    </div>
</section>

<div class="listing-layout">
    <aside class="listing-layout__filters">
        <?php View::render('site/partials/filters_form', [
            'filters' => $filters,
            'amenities' => $amenities,
            'action' => $baseUrl,
        ], null); ?>
    </aside>
    <div class="listing-layout__results">
        <?php View::render('site/partials/house_cards', [
            'houses' => $houses,
            'filters' => $filters,
            'total' => $total,
            'hasMore' => $hasMore,
            'baseUrl' => $baseUrl,
            'region' => $region,
        ], null); ?>
    </div>
</div>
