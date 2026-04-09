<?php
/**
 * index.php
 *
 * Forum homepage - displays all categories with thread/post counts and
 * last activity timestamps.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$pageTitle = FORUM_NAME . ' - Community Discussions';
$metaDesc  = 'Browse all discussion categories on ' . FORUM_NAME . '.';
$categories = getCategories();

require_once __DIR__ . '/templates/header.php';
?>

<div class="forum-hero">
    <h1 class="forum-hero__title"><?= e(FORUM_NAME) ?></h1>
    <p class="forum-hero__subtitle"><?= e(FORUM_TAGLINE) ?></p>
    <?php if (!isLoggedIn()): ?>
        <div class="forum-hero__actions">
            <a href="<?= SITE_URL ?>/register.php" class="btn btn--primary">Join the community</a>
            <a href="<?= SITE_URL ?>/login.php" class="btn btn--outline">Log in</a>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <p>No categories have been created yet.</p>
        <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn--primary">Add a category</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <section class="forum-index-section" aria-label="Forum categories">
        <div class="fi-table" role="table" aria-label="Forum categories">
            <div class="fi-table__head" role="rowgroup">
                <div class="fi-table__row fi-table__row--head" role="row">
                    <div class="fi-col-forum" role="columnheader">Forum</div>
                    <div class="fi-col-topics" role="columnheader">Topics</div>
                    <div class="fi-col-posts" role="columnheader">Posts</div>
                    <div class="fi-col-last" role="columnheader">Last Post</div>
                </div>
            </div>
            <div class="fi-table__body" role="rowgroup">
                <?php foreach ($categories as $cat): ?>
                    <?php require __DIR__ . '/templates/category_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
