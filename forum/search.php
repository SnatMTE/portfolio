<?php
/**
 * search.php
 *
 * Searches thread titles and displays paginated results.
 * URL: search.php?q={query}&page={n}
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$query       = trim($_GET['q'] ?? '');
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$results     = [];
$totalResults = 0;

if ($query !== '') {
    $totalResults = countSearchResults($query);
    $results      = searchThreads($query, $currentPage, THREADS_PER_PAGE);
}

$pageTitle = $query !== '' ? 'Search: ' . $query : 'Search';
$metaDesc  = 'Search for threads on ' . FORUM_NAME;

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="page-heading">Search</h1>

<form method="get" action="<?= SITE_URL ?>/search.php" class="search-form" role="search">
    <div class="search-form__row">
        <label for="q" class="sr-only">Search threads</label>
        <input type="search" name="q" id="q" class="form-control search-form__input"
               value="<?= e($query) ?>" placeholder="Search threads..." autofocus>
        <button type="submit" class="btn btn--primary">Search</button>
    </div>
</form>

<?php if ($query !== ''): ?>
    <p class="search-info">
        <?= $totalResults > 0
            ? number_format($totalResults) . ' result' . ($totalResults === 1 ? '' : 's') . ' for "' . e($query) . '"'
            : 'No results found for "' . e($query) . '"'
        ?>
    </p>

    <?php if (!empty($results)): ?>
        <div class="thread-list search-results" aria-label="Search results">
            <?php foreach ($results as $thread): ?>
                <article class="thread-row">
                    <div class="thread-row__flags"></div>
                    <div class="thread-row__main">
                        <h3 class="thread-row__title">
                            <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $thread['id'] ?>">
                                <?= e($thread['title']) ?>
                            </a>
                        </h3>
                        <div class="thread-row__meta">
                            In
                            <a href="<?= SITE_URL ?>/category.php?slug=<?= e($thread['category_slug']) ?>">
                                <?= e($thread['category_name']) ?>
                            </a>
                            &middot; By
                            <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $thread['user_id'] ?>">
                                <?= e($thread['author_name']) ?>
                            </a>
                            &middot; <?= e(formatDate($thread['created_at'])) ?>
                        </div>
                    </div>
                    <div class="thread-row__stats">
                        <span class="thread-row__stat">
                            <strong><?= number_format((int) $thread['post_count']) ?></strong>
                            <small>posts</small>
                        </span>
                    </div>
                    <div class="thread-row__last"></div>
                </article>
            <?php endforeach; ?>
        </div>

        <?= renderPagination(
            $totalResults,
            THREADS_PER_PAGE,
            $currentPage,
            SITE_URL . '/search.php?q=' . urlencode($query)
        ) ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
