<?php
/**
 * index.php
 *
 * Public file listing page. Supports search, category filtering, and pagination.
 * Only shows publicly visible files to unauthenticated visitors.
 * Logged-in admins see all files including private ones.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

const FILES_PER_PAGE = 12;

$isAdmin    = !empty($_SESSION['admin_id']);
$visibility = $isAdmin ? 'all' : 'public';

$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$offset   = ($page - 1) * FILES_PER_PAGE;

$total = countDownloads($visibility, $search, $category);
$files = getDownloads($visibility, $search, $category, FILES_PER_PAGE, $offset);

// Build a clean base URL for pagination that preserves current filters
$baseParams = array_filter([
    'q'        => $search,
    'category' => $category,
]);
$baseUrl = SITE_URL . '/index.php' . ($baseParams ? '?' . http_build_query($baseParams) : '');

$pageTitle = 'Downloads';
$metaDesc  = SITE_TAGLINE;

if ($search !== '') {
    $pageTitle = 'Search: ' . $search;
} elseif ($category !== '') {
    $pageTitle = $category . ' Downloads';
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="page-heading-bar">
    <h1 class="page-heading">
        <?php if ($search !== ''): ?>
            Search results for "<?= e($search) ?>"
        <?php elseif ($category !== ''): ?>
            <?= e($category) ?>
        <?php else: ?>
            All Downloads
        <?php endif; ?>
    </h1>

    <div class="page-heading-bar__right">
        <span class="result-count"><?= number_format($total) ?> file<?= $total !== 1 ? 's' : '' ?></span>
        <?php if ($isAdmin): ?>
            <a href="<?= SITE_URL ?>/upload.php" class="btn btn--primary btn--sm">+ Upload File</a>
        <?php endif; ?>
    </div>
</div>

<!-- Active filter strip -->
<?php if ($search !== '' || $category !== ''): ?>
    <div class="filter-strip">
        <?php if ($category !== ''): ?>
            <span class="filter-pill">
                Category: <strong><?= e($category) ?></strong>
                <a href="<?= SITE_URL ?>?<?= $search !== '' ? 'q=' . urlencode($search) : '' ?>"
                   class="filter-pill__remove" aria-label="Remove category filter">&#10005;</a>
            </span>
        <?php endif; ?>
        <?php if ($search !== ''): ?>
            <span class="filter-pill">
                Search: <strong><?= e($search) ?></strong>
                <a href="<?= SITE_URL ?>?<?= $category !== '' ? 'category=' . urlencode($category) : '' ?>"
                   class="filter-pill__remove" aria-label="Remove search filter">&#10005;</a>
            </span>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>" class="filter-strip__clear">Clear all</a>
    </div>
<?php endif; ?>

<?php if (empty($files)): ?>
    <div class="empty-state">
        <span class="empty-state__icon" aria-hidden="true">&#128190;</span>
        <p>No files found<?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>.</p>
        <?php if ($isAdmin): ?>
            <a href="<?= SITE_URL ?>/upload.php" class="btn btn--primary">Upload the first file →</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="file-grid">
        <?php foreach ($files as $file): ?>
            <?php require __DIR__ . '/templates/file_item.php'; ?>
        <?php endforeach; ?>
    </div>

    <?= renderPagination($total, FILES_PER_PAGE, $page, $baseUrl) ?>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
