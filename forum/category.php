<?php
/**
 * category.php
 *
 * Lists all threads within a forum category, with pagination.
 * URL: category.php?slug={slug}
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    redirect(SITE_URL . '/');
}

$category = getCategoryBySlug($slug);
if ($category === null) {
    http_response_code(404);
    $pageTitle = 'Category Not Found';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="empty-state"><h1>Category not found.</h1><a href="' . SITE_URL . '/" class="btn btn--primary">Back to homepage</a></div>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

$currentPage  = max(1, (int) ($_GET['page'] ?? 1));
$totalThreads = countThreadsByCategory((int) $category['id']);
$threads      = getThreadsByCategory((int) $category['id'], $currentPage, THREADS_PER_PAGE);

$pageTitle = e($category['name']);
$metaDesc  = $category['description'] ?: 'Browse threads in ' . $category['name'];

require_once __DIR__ . '/templates/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="<?= SITE_URL ?>">Home</a></li>
        <li aria-current="page"><?= e($category['name']) ?></li>
    </ol>
</nav>

<div class="page-top">
    <div class="page-top__info">
        <h1 class="page-heading"><?= e($category['name']) ?></h1>
        <?php if ($category['description']): ?>
            <p class="page-desc"><?= e($category['description']) ?></p>
        <?php endif; ?>
    </div>
    <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/create_thread.php?category_id=<?= (int) $category['id'] ?>"
           class="btn btn--primary">+ New Thread</a>
    <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn btn--outline">Log in to post</a>
    <?php endif; ?>
</div>

<?php if (empty($threads)): ?>
    <div class="empty-state">
        <p>No threads in this category yet. Be the first to start a discussion!</p>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/create_thread.php?category_id=<?= (int) $category['id'] ?>"
               class="btn btn--primary">Start a thread</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="thread-list-header">
        <span class="thread-list-header__col thread-list-header__col--main">Thread</span>
        <span class="thread-list-header__col">Posts</span>
        <span class="thread-list-header__col">Views</span>
        <span class="thread-list-header__col">Last Post</span>
    </div>

    <div class="thread-list" aria-label="Threads in <?= e($category['name']) ?>">
        <?php foreach ($threads as $thread): ?>
            <?php require __DIR__ . '/templates/thread_row.php'; ?>
        <?php endforeach; ?>
    </div>

    <?= renderPagination(
        $totalThreads,
        THREADS_PER_PAGE,
        $currentPage,
        SITE_URL . '/category.php?slug=' . urlencode($slug)
    ) ?>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
