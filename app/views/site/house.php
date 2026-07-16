<?php
/** @var array<string,mixed> $house */
/** @var string $localizedTitle */
/** @var string $localizedDescription */
/** @var bool $descriptionIsFallback */
/** @var string $regionName */
/** @var array<int,array<string,mixed>> $photos */
/** @var array<int,array<string,mixed>> $amenities */
/** @var DateTimeImmutable $calFrom */
/** @var DateTimeImmutable $calTo */
/** @var array<string,int> $busy */

$lang = Lang::current();
$houseUrl = SITE_BASE_URL . '/ev/' . $house['slug'];
$ownerYear = $house['owner_created_at'] ? date('Y', strtotime((string) $house['owner_created_at'])) : '';

$localeMap = ['az' => 'az_AZ', 'ru' => 'ru_RU', 'en' => 'en_US'];
$monthLabel = static function (DateTimeImmutable $d) use ($localeMap, $lang): string {
    if (class_exists('IntlDateFormatter')) {
        $fmt = new IntlDateFormatter($localeMap[$lang] ?? 'en_US', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'LLLL yyyy');
        $label = $fmt->format($d);
        if (is_string($label) && $label !== '') {
            return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
        }
    }
    return $d->format('F Y');
};

$renderMonth = static function (DateTimeImmutable $monthStart) use ($busy, $monthLabel): void {
    $year = (int) $monthStart->format('Y');
    $month = (int) $monthStart->format('n');
    $daysInMonth = (int) $monthStart->format('t');
    $firstWeekday = (int) $monthStart->format('N');
    $todayStr = (new DateTimeImmutable('today'))->format('Y-m-d');
    ?>
    <div class="cal-month">
        <h4><?= View::e($monthLabel($monthStart)) ?></h4>
        <div class="cal-grid">
            <?php for ($i = 1; $i < $firstWeekday; $i++): ?>
                <span class="cal-cell cal-cell--empty"></span>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $isBusy = isset($busy[$dateStr]);
                $isPast = $dateStr < $todayStr;
            ?>
                <span class="cal-cell<?= $isBusy ? ' is-busy' : '' ?><?= $isPast ? ' is-past' : '' ?>"><?= $d ?></span>
            <?php endfor; ?>
        </div>
    </div>
    <?php
};
?>
<article class="house-page">
    <section class="gallery" id="gallery">
        <?php if ($photos !== []): ?>
            <div class="gallery__track">
                <?php foreach ($photos as $i => $p): ?>
                    <?php if ($p['is_video']): ?>
                        <video class="gallery__slide" src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" controls playsinline<?= $i === 0 ? ' autoplay muted' : '' ?>></video>
                    <?php else: ?>
                        <img class="gallery__slide" src="/uploads/houses/<?= (int) $house['id'] ?>/<?= View::e($p['filename']) ?>" alt="<?= View::e($localizedTitle) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="gallery__dots"><?php foreach ($photos as $i => $p): ?><span class="gallery__dot<?= $i === 0 ? ' is-active' : '' ?>"></span><?php endforeach; ?></div>
        <?php else: ?>
            <div class="gallery__placeholder" aria-hidden="true">🏡</div>
        <?php endif; ?>
    </section>

    <div class="house-page__layout">
        <div class="house-page__main">
            <header class="house-page__header">
                <h1><?= View::e($localizedTitle) ?></h1>
                <p class="house-page__location"><?= View::e($regionName) ?><?= $house['village'] ? ' · ' . View::e($house['village']) : '' ?></p>
                <p class="house-page__price">
                    <strong><?= number_format((float) $house['price_night'], 0) ?> AZN</strong> / <?= View::e(Lang::t('house.per_night')) ?>
                    <?php if ($house['price_weekend']): ?>
                        <span class="house-page__price-weekend"><?= number_format((float) $house['price_weekend'], 0) ?> AZN / <?= View::e(Lang::t('house.per_weekend')) ?></span>
                    <?php endif; ?>
                </p>
            </header>

            <div class="stat-bar">
                <span><?= (int) $house['capacity'] ?> <?= View::e(Lang::t('house.capacity')) ?></span>
                <span><?= (int) $house['rooms'] ?> <?= View::e(Lang::t('house.rooms')) ?></span>
                <span><?= View::e(Lang::t('house.whole_house')) ?></span>
            </div>

            <?php if ($amenities !== []): ?>
            <section class="amenities-grid">
                <h2><?= View::e(Lang::t('house.amenities_title')) ?></h2>
                <div class="amenities-grid__items">
                    <?php foreach ($amenities as $a):
                        $name = match ($lang) { 'ru' => $a['name_ru'], 'en' => $a['name_en'], default => $a['name_az'] };
                    ?>
                        <span class="amenity-pill"><?= View::e($a['icon']) ?> <?= View::e($name) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="description">
                <h2><?= View::e(Lang::t('house.description_title')) ?></h2>
                <?php if ($descriptionIsFallback): ?>
                    <p class="description__note"><?= View::e(Lang::t('house.description_note')) ?></p>
                <?php endif; ?>
                <p><?= nl2br(View::e((string) $localizedDescription)) ?></p>
            </section>

            <section class="calendar">
                <h2><?= View::e(Lang::t('house.calendar_title')) ?></h2>
                <div class="cal-months">
                    <?php $renderMonth($calFrom); ?>
                    <?php $renderMonth($calFrom->modify('first day of next month')); ?>
                </div>
                <p class="calendar__note"><?= View::e(Lang::t('house.calendar_note')) ?></p>
            </section>

            <?php if ($house['map_lat'] && $house['map_lng']):
                $lat = (float) $house['map_lat'];
                $lng = (float) $house['map_lng'];
                $d = 0.01;
                $bbox = ($lng - $d) . ',' . ($lat - $d) . ',' . ($lng + $d) . ',' . ($lat + $d);
                $mapSrc = "https://www.openstreetmap.org/export/embed.html?bbox={$bbox}&marker={$lat},{$lng}&layer=mapnik";
            ?>
            <section class="map">
                <h2><?= View::e(Lang::t('house.map_title')) ?></h2>
                <iframe class="map__frame" src="<?= View::e($mapSrc) ?>" loading="lazy" title="<?= View::e(Lang::t('house.map_title')) ?>"></iframe>
            </section>
            <?php endif; ?>

            <section class="owner-card">
                <h2><?= View::e(Lang::t('house.owner_title')) ?></h2>
                <p class="owner-card__name"><?= View::e($house['owner_name']) ?> <span class="badge badge--verified"><?= View::e(Lang::t('house.owner_verified')) ?></span></p>
                <?php if ($ownerYear): ?><p class="owner-card__since"><?= View::e(Lang::tf('house.owner_since', $ownerYear)) ?></p><?php endif; ?>
            </section>
        </div>
    </div>

    <div class="sticky-panel">
        <div class="sticky-panel__price"><?= number_format((float) $house['price_night'], 0) ?> AZN <span>/ <?= View::e(Lang::t('house.per_night')) ?></span></div>
        <button type="button" id="wa-contact-btn" class="btn btn--primary"
            data-house-id="<?= (int) $house['id'] ?>"
            data-title="<?= View::e($localizedTitle) ?>"
            data-region="<?= View::e($regionName) ?>"
            data-phone="<?= View::e($house['whatsapp_phone']) ?>"
            data-house-url="<?= View::e($houseUrl) ?>"
            data-checkin="<?= View::e((string) ($_GET['checkin'] ?? '')) ?>"
            data-checkout="<?= View::e((string) ($_GET['checkout'] ?? '')) ?>"
            data-guests="<?= View::e((string) ($_GET['guests'] ?? '')) ?>"
            data-t-greeting="<?= View::e(Lang::t('wa.greeting')) ?>"
            data-t-about="<?= View::e(Lang::t('wa.about')) ?>"
            data-t-dates="<?= View::e(Lang::t('wa.dates')) ?>"
            data-t-guests="<?= View::e(Lang::t('wa.guests')) ?>"
            data-t-via="<?= View::e(Lang::t('wa.via')) ?>"
            data-track-url="/api/track/wa">
            <?= View::e(Lang::t('house.wa_button')) ?>
        </button>
    </div>
</article>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'LodgingBusiness',
    'name' => $localizedTitle,
    'address' => [
        '@type' => 'PostalAddress',
        'addressRegion' => $regionName,
        'addressCountry' => 'AZ',
    ],
    'priceRange' => number_format((float) $house['price_night'], 0) . ' AZN',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
