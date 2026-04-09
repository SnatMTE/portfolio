<?php
/**
 * templates/category_card.php
 *
 * Renders a single category card for the forum homepage.
 * Expects $cat (array) with keys from getCategories():
 *   id, name, slug, description, thread_count, post_count, last_post_at
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */
?>
<article class="category-card">
    <a href="<?= SITE_URL ?>/category.php?slug=<?= e($cat['slug']) ?>" class="category-card__inner">
        <div class="category-card__icon" aria-hidden="true">
            <?= strtoupper(mb_substr($cat['name'], 0, 1)) ?>
        </div>
        <div class="category-card__body">
            <h2 class="category-card__title"><?= e($cat['name']) ?></h2>
            <?php if ($cat['description']): ?>
                <p class="category-card__desc"><?= e($cat['description']) ?></p>
            <?php endif; ?>
            <div class="category-card__stats">
                <span class="category-card__stat">
                    <strong><?= number_format((int) $cat['thread_count']) ?></strong> threads
                </span>
                <span class="category-card__stat">
                    <strong><?= number_format((int) $cat['post_count']) ?></strong> posts
                </span>
                <?php if (!empty($cat['last_post_at'])): ?>
                    <span class="category-card__stat category-card__stat--last">
                        Last activity <?= e(formatDate($cat['last_post_at'])) ?>
                    </span>
                <?php else: ?>
                    <span class="category-card__stat category-card__stat--last">No posts yet</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="category-card__arrow" aria-hidden="true">&rsaquo;</div>
    </a>
</article>
