<?php
/** @var array<int,array<string,mixed>> $houses */
/** @var int $selectedId */
/** @var array<string,int> $busy */
?>
<section class="owner-calendar">
    <h1><?= View::e(Lang::t('owner.calendar_title')) ?></h1>

    <?php if ($houses === []): ?>
        <p><?= View::e(Lang::t('owner.calendar_no_houses')) ?></p>
        <a class="btn btn--primary" href="/sahib/ev/yeni"><?= View::e(Lang::t('owner.dashboard_add_house')) ?></a>
    <?php else: ?>
        <?php if (count($houses) > 1): ?>
        <form method="get" action="/sahib/teqvim" class="calendar-house-select">
            <label><?= View::e(Lang::t('owner.calendar_select_house')) ?>
                <select name="ev" onchange="this.form.submit()">
                    <?php foreach ($houses as $h): ?>
                        <option value="<?= (int) $h['id'] ?>" <?= (int) $h['id'] === $selectedId ? 'selected' : '' ?>><?= View::e($h['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <?php endif; ?>

        <p class="calendar-hint"><?= View::e(Lang::t('owner.calendar_hint')) ?></p>

        <div class="owner-cal-grid" id="owner-cal"
             data-house-id="<?= $selectedId ?>"
             data-toggle-url="/sahib/teqvim/<?= $selectedId ?>/gun"
             data-range-url="/sahib/teqvim/<?= $selectedId ?>/aralig"
             data-csrf="<?= View::e(Csrf::token()) ?>">
            <?php
            $today = new DateTimeImmutable('today');
            for ($m = 0; $m < 6; $m++):
                $monthStart = (new DateTimeImmutable('first day of this month'))->modify("+{$m} months");
                $year = (int) $monthStart->format('Y');
                $month = (int) $monthStart->format('n');
                $daysInMonth = (int) $monthStart->format('t');
                $firstWeekday = (int) $monthStart->format('N');
            ?>
                <div class="cal-month cal-month--editable">
                    <h4><?= View::e($monthStart->format('F Y')) ?></h4>
                    <div class="cal-grid">
                        <?php for ($i = 1; $i < $firstWeekday; $i++): ?><span class="cal-cell cal-cell--empty"></span><?php endfor; ?>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++):
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $isBusy = isset($busy[$dateStr]);
                            $isPast = $dateStr < $today->format('Y-m-d');
                        ?>
                            <button type="button" class="cal-cell cal-day<?= $isBusy ? ' is-busy' : '' ?><?= $isPast ? ' is-past' : '' ?>"
                                <?= $isPast ? 'disabled' : '' ?> data-date="<?= $dateStr ?>"><?= $d ?></button>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <form class="calendar-range-form" id="calendar-range-form"
              data-range-url="/sahib/teqvim/<?= $selectedId ?>/aralig"
              data-csrf="<?= View::e(Csrf::token()) ?>">
            <label><?= View::e(Lang::t('owner.calendar_range_from')) ?> <input type="date" name="from" required></label>
            <label><?= View::e(Lang::t('owner.calendar_range_to')) ?> <input type="date" name="to" required></label>
            <button type="submit" data-busy="1" class="btn"><?= View::e(Lang::t('owner.calendar_range_mark_busy')) ?></button>
            <button type="submit" data-busy="0" class="btn"><?= View::e(Lang::t('owner.calendar_range_mark_free')) ?></button>
        </form>
    <?php endif; ?>
</section>
