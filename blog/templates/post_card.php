<?php
/**
 * templates/post_card.php
 *
 * Renders a single post preview card for use on the blog listing page,
 * category archive, tag archive, and search results page.
 *
 * Expects the following variable to be in scope:
 *   $post  (array)  – A post row returned by getPosts() or similar queries.
 *                     Keys used: id, title, slug, excerpt, featured_image,
 *                                author_name, category_name, category_slug,
 *                                created_at.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

// Build the post URL using the clean slug when available and supported,
// otherwise fall back to the numeric ID form (post.php?id=...)
$useSlug = !empty($post['slug']) && function_exists('supportsPrettyUrls') && supportsPrettyUrls();
$postUrl = $useSlug
    ? SITE_URL . '/post/' . e($post['slug'])
    : SITE_URL . '/post.php?id=' . (int) $post['id'];
?>
<article class="post-card">
    <?php if (!empty($post['featured_image'])): ?>
        <a href="<?= $postUrl ?>" class="post-card__image-link" tabindex="-1" aria-hidden="true">
            <img
                src="<?= SITE_URL ?>/assets/images/uploads/<?= e($post['featured_image']) ?>"
                alt="<?= e($post['title']) ?>"
                class="post-card__image"
                loading="lazy"
            >
        </a>
    <?php endif; ?>

    <div class="post-card__body">
        <div class="post-card__meta">
            <?php if (!empty($post['category_name'])): ?>
                <a href="<?= SITE_URL ?>/category/<?= e($post['category_slug']) ?>" class="post-card__category">
                    <?= e($post['category_name']) ?>
                </a>
            <?php endif; ?>
            <time datetime="<?= e($post['created_at']) ?>" class="post-card__date">
                <?= formatDate($post['created_at']) ?>
            </time>
        </div>

        <h2 class="post-card__title">
            <a href="<?= $postUrl ?>"><?= e($post['title']) ?></a>
        </h2>

        <?php if (!empty($post['excerpt'])): ?>
            <p class="post-card__excerpt"><?= e($post['excerpt']) ?></p>
        <?php endif; ?>

        <div class="post-card__footer">
            <span class="post-card__author">By <?= e($post['author_name'] ?? DEFAULT_AUTHOR) ?></span>
            <a href="<?= $postUrl ?>" class="btn btn--sm btn--outline">Read more &rarr;</a>
        </div>
    </div>
</article>
