<?php
/**
 * templates/event_item.php
 *
 * Renders a single event as a compact list/card element.
 *
 * Expects:
 *   $event  (array)  – Row from cal_events.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>
<article class="event-item">
    <div class="event-item__date">
        <span class="event-item__day"><?= (new DateTime($event['start_datetime']))->format('d') ?></span>
        <span class="event-item__month"><?= (new DateTime($event['start_datetime']))->format('M') ?></span>
    </div>
    <div class="event-item__body">
        <h3 class="event-item__title">
            <a href="<?= SITE_URL ?>/event.php?id=<?= (int) $event['id'] ?>">
                <?= e($event['title']) ?>
            </a>
        </h3>
        <p class="event-item__meta">
            <?= e(formatDatetime($event['start_datetime'])) ?>
            &ndash; <?= e(formatDatetime($event['end_datetime'])) ?>
        </p>
        <?php if (!empty($event['location'])): ?>
            <p class="event-item__location"><?= e($event['location']) ?></p>
        <?php endif; ?>
        <?php if (!empty($event['description'])): ?>
            <p class="event-item__desc">
                <?= e(mb_substr($event['description'], 0, 140)) ?>
                <?php if (mb_strlen($event['description']) > 140): ?>&hellip;<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</article>
