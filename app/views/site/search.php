<?php
/** @var array<int,array<string,mixed>> $regions */
/** @var array<string,mixed>|null $selectedRegion */
?>
<section class="page-header">
    <h1><?= View::e(Lang::t('search.title')) ?></h1>
    <p class="page-header__count"><?= View::e(Lang::tf('listing.house_count', $total)) ?></p>
</section>

<div class="listing-layout">
    <aside class="listing-layout__filters">
        <?php View::render('site/partials/filters_form', [
            'filters' => $filters,
            'amenities' => $amenities,
            'action' => $baseUrl,
            'regions' => $regions,
            'selectedRegion' => $selectedRegion,
        ], null); ?>
    </aside>
    <div class="listing-layout__results">
        <?php View::render('site/partials/house_cards', [
            'houses' => $houses,
            'filters' => $filters,
            'total' => $total,
            'hasMore' => $hasMore,
            'baseUrl' => $baseUrl,
            'selectedRegion' => $selectedRegion,
        ], null); ?>
    </div>
</div>
