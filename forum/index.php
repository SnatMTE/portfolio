<?php
/**
 * index.php
 *
 * Forum homepage - displays all categories with thread/post counts and
 * last activity timestamps.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$pageTitle = SITE_NAME . ' - Community Discussions';
$metaDesc  = 'Browse all discussion categories on ' . SITE_NAME . '.';
$categories = getCategories();

require_once __DIR__ . '/templates/header.php';
?>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <p>No categories have been created yet.</p>
        <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn--primary">Add a category</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <ul class="category-list">
        <?php foreach ($categories as $cat): ?>
            <li class="category-list__item">
                <a href="<?= SITE_URL ?>/category/<?= e($cat['slug']) ?>">
                    <?= e($cat['name']) ?>
                </a>
                <p class="category-list__description"><?= e($cat['description']) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
