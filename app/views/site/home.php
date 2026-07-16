<?php
/** @var array<int,array<string,mixed>> $regions */
/** @var array<int,array<string,mixed>> $popular */
/** @var array<int,array{label:string,amenity_id:int}> $chips */
$lang = Lang::current();
$emptyFilters = ['checkin' => null, 'checkout' => null, 'guests' => null];
?>
<section class="hero">
    <h1><?= View::e(Lang::t('home.hero_title')) ?></h1>
    <p class="hero__subtitle"><?= View::e(Lang::t('home.hero_subtitle')) ?></p>

    <form class="search-box" method="get" action="/axtar">
        <div class="search-box__field">
            <label for="s-region"><?= View::e(Lang::t('home.search_region')) ?></label>
            <select id="s-region" name="region">
                <option value="hamisi"><?= View::e(Lang::t('home.search_region_all')) ?></option>
                <?php foreach ($regions as $r): ?>
                    <option value="<?= View::e($r['slug']) ?>"><?= View::e($r['name_az']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-box__field">
            <label for="s-checkin"><?= View::e(Lang::t('home.search_checkin')) ?></label>
            <input type="date" id="s-checkin" name="checkin">
        </div>
        <div class="search-box__field">
            <label for="s-checkout"><?= View::e(Lang::t('home.search_checkout')) ?></label>
            <input type="date" id="s-checkout" name="checkout">
        </div>
        <div class="search-box__field">
            <label for="s-guests"><?= View::e(Lang::t('home.search_guests')) ?></label>
            <input type="number" id="s-guests" name="guests" min="1" placeholder="2">
        </div>
        <button type="submit" class="btn btn--primary search-box__submit"><?= View::e(Lang::t('home.search_submit')) ?></button>
    </form>

    <nav class="chips">
        <a class="chip" href="/axtar"><?= View::e(Lang::t('home.chip_all')) ?></a>
        <?php foreach ($chips as $chip): ?>
            <a class="chip" href="/axtar?amenities[]=<?= (int) $chip['amenity_id'] ?>"><?= View::e($chip['label']) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<section class="regions-strip" id="bolgeler">
    <h2><?= View::e(Lang::t('home.regions_title')) ?></h2>
    <div class="regions-strip__row">
        <?php foreach ($regions as $r):
            $name = match ($lang) { 'ru' => $r['name_ru'], 'en' => $r['name_en'], default => $r['name_az'] };
        ?>
            <a class="region-card<?= $r['cover_img'] ? '' : ' region-card--placeholder' ?>" href="/bolge/<?= View::e($r['slug']) ?>"
               <?= $r['cover_img'] ? 'style="background-image:url(\'' . View::e($r['cover_img']) . '\')"' : '' ?>>
                <span class="region-card__name"><?= View::e($name) ?></span>
                <span class="region-card__count"><?= View::e(Lang::tf('listing.house_count', (int) $r['house_count'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($popular !== []): ?>
<section class="popular">
    <h2><?= View::e(Lang::t('home.popular_title')) ?></h2>
    <div class="house-grid">
        <?php foreach ($popular as $house): ?>
            <?php View::render('site/partials/house_card', ['house' => $house, 'filters' => $emptyFilters], null); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
