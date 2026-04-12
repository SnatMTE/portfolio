<?php
/**
 * index.php
 *
 * Product listing page – the store homepage.
 * Displays active products in a responsive grid with optional
 * category filtering and keyword search.
 *
 * Query parameters
 * ----------------
 *   q         – Keyword search string
 *   category  – Category slug to filter by
 *   page      – Page number (default 1)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------------------------
// Resolve filters from query string
// ---------------------------------------------------------------------------
$search     = trim($_GET['q']        ?? '');
$catSlug    = trim($_GET['category'] ?? '');
$page       = max(1, (int) ($_GET['page'] ?? 1));
$category   = $catSlug !== '' ? getCategoryBySlug($catSlug) : null;
$categoryId = $category ? (int) $category['id'] : null;

// ---------------------------------------------------------------------------
// Fetch products and total count for pagination
// ---------------------------------------------------------------------------
$products   = getProducts($page, PRODUCTS_PER_PAGE, $categoryId, $search !== '' ? $search : null);
$totalCount = countProducts($categoryId, $search !== '' ? $search : null);
$totalPages = (int) ceil($totalCount / PRODUCTS_PER_PAGE);

// ---------------------------------------------------------------------------
// Build page title
// ---------------------------------------------------------------------------
if ($search !== '') {
    $pageTitle = 'Search: ' . $search;
    $metaDesc  = 'Products matching "' . $search . '" in ' . SITE_NAME;
} elseif ($category !== null) {
    $pageTitle = $category['name'];
    $metaDesc  = 'Browse ' . $category['name'] . ' products in ' . SITE_NAME;
} else {
    $pageTitle = 'All Products';
    $metaDesc  = SITE_TAGLINE;
}

require_once __DIR__ . '/templates/header.php';
?>

<?php renderFlash(); ?>

<!-- Page title + filter bar -->
<div class="page-header">
    <h1 class="page-title">
        <?php if ($search !== ''): ?>
            Search results for <em><?= e($search) ?></em>
        <?php elseif ($category !== null): ?>
            <?= e($category['name']) ?>
        <?php else: ?>
            All Products
        <?php endif; ?>
    </h1>

    <?php if ($totalCount > 0): ?>
        <p class="page-subtitle"><?= $totalCount ?> product<?= $totalCount !== 1 ? 's' : '' ?> found</p>
    <?php endif; ?>

    <!-- Category filter pills -->
    <?php
    $allCats = getAllCategories();
    if (count($allCats) > 0):
    ?>
    <div class="filter-pills">
        <a
            href="<?= SITE_URL ?>?<?= $search !== '' ? 'q=' . urlencode($search) : '' ?>"
            class="filter-pill <?= $categoryId === null ? 'is-active' : '' ?>"
        >All</a>
        <?php foreach ($allCats as $cat): ?>
            <a
                href="<?= SITE_URL ?>?category=<?= e($cat['slug']) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                class="filter-pill <?= (int) ($category['id'] ?? 0) === (int) $cat['id'] ? 'is-active' : '' ?>"
            >
                <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Product grid -->
<?php if (count($products) > 0): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php require __DIR__ . '/templates/product_card.php'; ?>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Product listing pages">
            <?php
            $queryBase = '';
            if ($search     !== '') $queryBase .= '&q='        . urlencode($search);
            if ($catSlug    !== '') $queryBase .= '&category='  . urlencode($catSlug);

            for ($p = 1; $p <= $totalPages; $p++):
                $href = SITE_URL . '?page=' . $p . $queryBase;
            ?>
                <a
                    href="<?= $href ?>"
                    class="pagination__link <?= $p === $page ? 'is-current' : '' ?>"
                    <?= $p === $page ? 'aria-current="page"' : '' ?>
                >
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state">
        <p>No products found<?= $search !== '' ? ' for <strong>' . e($search) . '</strong>' : '' ?>.</p>
        <?php if ($search !== '' || $category !== null): ?>
            <a href="<?= SITE_URL ?>" class="btn btn--primary">View all products</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
