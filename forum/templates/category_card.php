<?php
/**
 * templates/category_card.php
 *
 * Renders a single forum category row in the forum-index table.
 * Expects $cat (array) with keys from getCategories():
 *   id, name, slug, description, thread_count, post_count, last_post_at,
 *   last_thread_title, last_thread_slug, last_poster
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>
<div class="fi-table__row fi-row" role="row">
    <div class="fi-col-forum" role="cell">
        <div class="fi-row__icon" aria-hidden="true">
            <?= strtoupper(mb_substr($cat['name'], 0, 1)) ?>
        </div>
        <div class="fi-row__info">
            <a href="<?= SITE_URL ?>/category.php?slug=<?= e($cat['slug']) ?>" class="fi-row__name">
                <?= e($cat['name']) ?>
            </a>
            <?php if ($cat['description']): ?>
                <p class="fi-row__desc"><?= e($cat['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="fi-col-topics" role="cell">
        <span class="fi-stat__value"><?= number_format((int) $cat['thread_count']) ?></span>
        <span class="fi-stat__label">Topics</span>
    </div>
    <div class="fi-col-posts" role="cell">
        <span class="fi-stat__value"><?= number_format((int) $cat['post_count']) ?></span>
        <span class="fi-stat__label">Posts</span>
    </div>
    <div class="fi-col-last" role="cell">
        <?php if (!empty($cat['last_thread_title'])): ?>
            <a href="<?= SITE_URL ?>/thread.php?slug=<?= e($cat['last_thread_slug']) ?>" class="fi-last__thread" title="<?= e($cat['last_thread_title']) ?>">
                <?= e(truncate($cat['last_thread_title'], 45)) ?>
            </a>
            <span class="fi-last__by">
                by <strong><?= e($cat['last_poster']) ?></strong>
            </span>
            <time class="fi-last__time" datetime="<?= e($cat['last_post_at']) ?>">
                <?= e(formatDateTime($cat['last_post_at'])) ?>
            </time>
        <?php else: ?>
            <span class="fi-last__empty">No posts yet</span>
        <?php endif; ?>
    </div>
</div>
