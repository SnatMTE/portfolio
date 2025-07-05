<?php
/**
 * templates/calendar_grid.php
 *
 * Renders the monthly calendar grid.
 *
 * Expects variables in scope:
 *   $grid     (array)  – Output of buildMonthGrid()
 *   $year     (int)
 *   $month    (int)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

$prev = prevMonth($year, $month);
$next = nextMonth($year, $month);

$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
?>

<div class="cal-toolbar">
    <a href="<?= SITE_URL ?>?year=<?= $prev['year'] ?>&amp;month=<?= $prev['month'] ?>"
       class="btn btn--outline btn--sm" aria-label="Previous month">&#8249; Prev</a>

    <h2 class="cal-toolbar__title">
        <?= e(monthName($month)) ?> <?= $year ?>
    </h2>

    <a href="<?= SITE_URL ?>?year=<?= $next['year'] ?>&amp;month=<?= $next['month'] ?>"
       class="btn btn--outline btn--sm" aria-label="Next month">Next &#8250;</a>
</div>

<div class="cal-grid" role="grid" aria-label="<?= e(monthName($month)) ?> <?= $year ?>"
     data-base-url="<?= e(SITE_URL) ?>">

    <!-- Day-of-week header row -->
    <div class="cal-grid__header" role="row">
        <?php foreach ($dayNames as $dn): ?>
            <div class="cal-grid__day-name" role="columnheader"><?= $dn ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Week rows -->
    <?php foreach ($grid as $week): ?>
        <div class="cal-grid__week" role="row">
            <?php foreach ($week as $cell): ?>
                <?php if ($cell['day'] === null): ?>
                    <div class="cal-cell cal-cell--empty" role="gridcell"></div>
                <?php else: ?>
                    <div class="cal-cell <?= $cell['isToday'] ? 'cal-cell--today' : '' ?>"
                         role="gridcell"
                         data-date="<?= e($cell['date']) ?>">

                        <span class="cal-cell__day"><?= $cell['day'] ?></span>

                        <?php if (!empty($cell['events'])): ?>
                            <div class="cal-cell__events">
                                <?php foreach (array_slice($cell['events'], 0, 3) as $ev): ?>
                                    <a href="<?= SITE_URL ?>/event.php?id=<?= (int) $ev['id'] ?>"
                                       class="cal-event-chip"
                                       title="<?= e($ev['title']) ?>">
                                        <?= e($ev['title']) ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($cell['events']) > 3): ?>
                                    <span class="cal-event-more">
                                        +<?= count($cell['events']) - 3 ?> more
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?= SITE_URL ?>/create_event.php?date=<?= e($cell['date']) ?>"
                           class="cal-cell__add" aria-label="Add event on <?= e($cell['date']) ?>">+</a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</div>
