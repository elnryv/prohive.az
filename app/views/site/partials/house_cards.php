<?php
/** @var array<int,array<string,mixed>> $houses */
/** @var array<string,mixed> $filters */
/** @var int $total */
/** @var bool $hasMore */
/** @var string $baseUrl */
$page = (int) ($filters['page'] ?? 1);
?>
<?php if ($houses === [] && $page === 1): ?>
    <p class="listing-empty"><?= View::e(Lang::t(isset($region) ? 'listing.empty' : 'listing.empty_search')) ?></p>
<?php else: ?>
    <div class="house-grid" id="house-grid">
        <?php foreach ($houses as $house): ?>
            <?php View::render('site/partials/house_card', ['house' => $house, 'filters' => $filters], null); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($hasMore):
    // GET parametr adlarına uyğun bərpa (ListingFilters::parse 'amenities[]' oxuyur, 'amenity_ids' yox)
    $nextQuery = [
        'price_min' => $filters['price_min'] ?? null,
        'price_max' => $filters['price_max'] ?? null,
        'guests' => $filters['guests'] ?? null,
        'checkin' => $filters['checkin'] ?? null,
        'checkout' => $filters['checkout'] ?? null,
        'sort' => ($filters['sort'] ?? 'default') !== 'default' ? $filters['sort'] : null,
        'amenities' => $filters['amenity_ids'] ?? [],
    ];
    if (isset($region)) {
        // bölgə səhifəsində region query-yə düşmür (URL-in özündə var)
    } elseif (isset($selectedRegion) && $selectedRegion) {
        $nextQuery['region'] = $selectedRegion['slug'];
    }
    $nextQuery = array_filter($nextQuery, static fn ($v) => $v !== null && $v !== '' && $v !== []);
    ?>
    <button type="button" class="btn-load-more" id="load-more"
        data-base-url="<?= View::e($baseUrl) ?>"
        data-next-page="<?= $page + 1 ?>"
        data-query="<?= View::e(http_build_query($nextQuery)) ?>">
        <?= View::e(Lang::t('listing.load_more')) ?>
    </button>
<?php endif; ?>
