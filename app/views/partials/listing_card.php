<?php
/** @var array $listing */
/** @var string $href */
use App\Core\Icon;
use App\Core\Lang;

$statusChip = match ($listing['status']) {
    'active' => ['chip-active', 'listing.status_active'],
    'accepted' => ['chip-ok', 'listing.status_accepted'],
    'completed' => ['chip-muted', 'listing.status_completed'],
    'expired' => ['chip-muted', 'listing.status_expired'],
    'removed' => ['chip-muted', 'listing.status_removed'],
    default => ['chip-muted', 'listing.status_active'],
};
$routeMatched = !empty($listing['route_matched']);
?>
<a class="card" href="<?= e($href) ?>" style="display:block<?= $routeMatched ? ';border-color:var(--primary)' : '' ?>" data-listing-id="<?= (int) $listing['id'] ?>">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
    <div style="display:flex;align-items:center;gap:10px;min-width:0">
      <span class="icon-badge-sm"><?= icon(Icon::forCategorySlug($listing['category_slug'] ?? null), 'icon', 18) ?></span>
      <div class="route">
        <span><?= e(Lang::field($listing, 'from')) ?></span>
        <span class="arrow">→</span>
        <span><?= e(Lang::field($listing, 'to')) ?></span>
      </div>
    </div>
    <?php if ((int) $listing['is_urgent'] === 1): ?><span class="chip chip-urgent"><?= icon('zap', 'icon', 14) ?> <?= e(t('listing.urgent')) ?></span><?php endif; ?>
  </div>
  <div style="display:flex;gap:8px;align-items:center;margin:8px 0;flex-wrap:wrap">
    <?php if ($routeMatched): ?><span class="chip chip-match"><?= icon('map-pin', 'icon', 14) ?> <?= e(t('routes.match_badge')) ?></span><?php endif; ?>
    <span class="chip"><?= e(Lang::field($listing, 'cat')) ?></span>
    <span class="chip <?= $statusChip[0] ?>"><?= e(t($statusChip[1])) ?></span>
    <?php if (!empty($listing['offers_count'])): ?><span class="chip chip-warn"><?= e(t('listing.offers_count', ['n' => $listing['offers_count']])) ?></span><?php endif; ?>
  </div>
  <p class="text-soft" style="margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
    <?= e(mb_substr((string) $listing['description'], 0, 80)) ?>
  </p>
  <p class="text-soft" style="margin:0;font-size:12px">
    <?= $listing['move_date'] ? e($listing['move_date']) : e(t('common.agreement')) ?> · <?= e(time_ago($listing['created_at'])) ?>
  </p>
</a>
