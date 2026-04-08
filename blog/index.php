<?php
/**
 * index.php
 *
 * Blog listing homepage. Handles three display modes based on query string:
 *   - Default     → paginated list of all published posts
 *   - ?category=  → posts filtered by category slug
 *   - ?tag=       → posts filtered by tag slug
 *   - ?q=         → search results (via GET form or /search rewrite)
 *
 * Pagination is applied to all modes.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Determine display mode and collect posts
// ---------------------------------------------------------------------------

/** @var string $mode  One of 'search', 'category', 'tag', 'default'. */
$mode = 'default';

/** @var string $filterLabel  Human-readable label shown in the page heading. */
$filterLabel = 'Latest Posts';

/** @var string|null $activeCategory  Slug of the selected category (if any). */
$activeCategory = null;

/** @var string|null $activeTag  Slug of the selected tag (if any). */
$activeTag = null;

/** @var string $searchQuery  Sanitised search string. */
$searchQuery = '';

// Current page (validated to positive int)
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

if (!empty($_GET['q'])) {
    $mode        = 'search';
    $searchQuery = trim($_GET['q']);
    // Strip characters that have no meaning in our LIKE query
    $searchQuery = substr(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $searchQuery), 0, 100);
    $total       = countSearchPosts($searchQuery);
    $posts       = searchPosts($searchQuery, $currentPage);
    $filterLabel = 'Search: "' . e($searchQuery) . '"';
    $baseUrl     = SITE_URL . '/search?q=' . urlencode($searchQuery) . '&page=';
} elseif (!empty($_GET['category'])) {
    $mode           = 'category';
    $activeCategory = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['category']));
    $total          = countPostsByCategory($activeCategory);
    $posts          = getPostsByCategory($activeCategory, $currentPage);
    // Resolve human-readable name from categories list
    $cats = getAllCategories();
    foreach ($cats as $c) {
        if ($c['slug'] === $activeCategory) {
            $filterLabel = 'Category: ' . $c['name'];
            break;
        }
    }
    $baseUrl = SITE_URL . '/category/' . $activeCategory . '?page=';
} elseif (!empty($_GET['tag'])) {
    $mode      = 'tag';
    $activeTag = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['tag']));
    $total     = countPostsByTag($activeTag);
    $posts     = getPostsByTag($activeTag, $currentPage);
    $tags      = getAllTags();
    foreach ($tags as $t) {
        if ($t['slug'] === $activeTag) {
            $filterLabel = 'Tag: ' . $t['name'];
            break;
        }
    }
    $baseUrl = SITE_URL . '/tag/' . $activeTag . '?page=';
} else {
    $total   = countPosts();
    $posts   = getPosts($currentPage);
    $baseUrl = SITE_URL . '/?page=';
}

$pagination = buildPagination($currentPage, $total, $baseUrl);
$allTags    = getAllTags();

$pageTitle = ($mode === 'default') ? SITE_NAME : strip_tags($filterLabel) . ' – ' . SITE_NAME;
$metaDesc  = SITE_TAGLINE;

require_once __DIR__ . '/templates/header.php';
?>

<section class="archive-header">
    <h1 class="page-heading"><?= $filterLabel ?></h1>
    <?php if ($mode !== 'default'): ?>
        <p><?= $total ?> post<?= $total !== 1 ? 's' : '' ?> found.</p>
    <?php endif; ?>
</section>

<?php if (!empty($allTags)): ?>
<div class="tag-cloud" aria-label="Browse by tag">
    <?php foreach ($allTags as $tag): ?>
        <a href="<?= SITE_URL ?>/tag/<?= e($tag['slug']) ?>"
           class="tag-pill <?= ($activeTag === $tag['slug']) ? 'tag-pill--active' : '' ?>">
            <?= e($tag['name']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <h2>No posts found.</h2>
        <p><?= $mode === 'search' ? 'Try a different search term.' : 'Check back soon!' ?></p>
        <a href="<?= SITE_URL ?>" class="btn btn--primary" style="margin-top:1rem;">Go Home</a>
    </div>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <?php require __DIR__ . '/templates/post_card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total'] > 1): ?>
        <nav class="pagination" aria-label="Page navigation">
            <?php if ($pagination['hasPrev']): ?>
                <a href="<?= $pagination['baseUrl'] . ($currentPage - 1) ?>">&larr; Prev</a>
            <?php else: ?>
                <span class="disabled">&larr; Prev</span>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pagination['total']; $p++): ?>
                <?php if ($p === $currentPage): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $pagination['baseUrl'] . $p ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagination['hasNext']): ?>
                <a href="<?= $pagination['baseUrl'] . ($currentPage + 1) ?>">Next &rarr;</a>
            <?php else: ?>
                <span class="disabled">Next &rarr;</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
