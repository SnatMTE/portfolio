<?php
/**
 * admin/files.php
 *
 * Full file management table. Supports search, category filter, visibility
 * toggle, and inline delete with CSRF confirmation.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

requireLogin();

const ADMIN_FILES_PER_PAGE = 20;

$search     = trim($_GET['q']          ?? '');
$category   = trim($_GET['category']   ?? '');
$visibility = trim($_GET['visibility'] ?? 'all');
$page       = max(1, (int) ($_GET['page'] ?? 1));
$offset     = ($page - 1) * ADMIN_FILES_PER_PAGE;

if (!in_array($visibility, ['all', 'public', 'private'], true)) {
    $visibility = 'all';
}

$total = countDownloads($visibility, $search, $category);
$files = getDownloads($visibility, $search, $category, ADMIN_FILES_PER_PAGE, $offset);

$categories = getCategories();

$baseParams = array_filter([
    'q'          => $search,
    'category'   => $category,
    'visibility' => $visibility !== 'all' ? $visibility : '',
]);
$baseUrl = SITE_URL . '/admin/files.php' . ($baseParams ? '?' . http_build_query($baseParams) : '');

$currentAdminPage = 'files';

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>All Files – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>All Files <span style="font-size:.9rem;font-weight:400;color:var(--clr-text-muted)">(<?= number_format($total) ?>)</span></h1>
            <a href="<?= SITE_URL ?>/upload.php" class="btn btn--primary btn--sm">+ Upload File</a>
        </div>

        <?php renderFlash(); ?>

        <!-- Filters -->
        <form method="get" action="<?= SITE_URL ?>/admin/files.php" class="admin-filter-bar">
            <input type="text" name="q" class="form-control form-control--inline"
                   placeholder="Search files…" value="<?= e($search) ?>" maxlength="150">

            <select name="category" class="form-control form-control--inline">
                <option value="">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="visibility" class="form-control form-control--inline">
                <option value="all"     <?= $visibility === 'all'     ? 'selected' : '' ?>>All visibility</option>
                <option value="public"  <?= $visibility === 'public'  ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Private</option>
            </select>

            <button type="submit" class="btn btn--outline btn--sm">Filter</button>
            <?php if ($search !== '' || $category !== '' || $visibility !== 'all'): ?>
                <a href="<?= SITE_URL ?>/admin/files.php" class="btn btn--sm"
                   style="color:var(--clr-text-muted)">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($files)): ?>
            <p style="color:var(--clr-text-muted);margin-top:2rem;">
                No files found.
                <?php if ($search !== '' || $category !== ''): ?>
                    <a href="<?= SITE_URL ?>/admin/files.php">Clear filters</a>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Category</th>
                        <th>Size</th>
                        <th>Visibility</th>
                        <th>Downloads</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $f): ?>
                        <tr>
                            <td>
                                <span style="margin-right:.4rem;" aria-hidden="true">
                                    <?= fileTypeIcon($f['mime_type']) ?>
                                </span>
                                <strong><?= e($f['title']) ?></strong><br>
                                <small style="color:var(--clr-text-muted)"><?= e($f['original_name']) ?></small>
                            </td>
                            <td><?= $f['category'] !== '' ? e($f['category']) : '<span style="color:var(--clr-text-muted)">—</span>' ?></td>
                            <td><?= e(formatFileSize((int) $f['file_size'])) ?></td>
                            <td>
                                <span class="badge badge--<?= e($f['visibility']) ?>">
                                    <?= e($f['visibility']) ?>
                                </span>
                            </td>
                            <td><?= number_format((int) $f['download_count']) ?></td>
                            <td><?= e(formatDate($f['created_at'])) ?></td>
                            <td style="white-space:nowrap">
                                <a href="<?= SITE_URL ?>/download.php?id=<?= (int) $f['id'] ?>"
                                   class="btn btn--sm btn--outline" title="Download">&#8595;</a>
                                <a href="<?= SITE_URL ?>/edit.php?id=<?= (int) $f['id'] ?>"
                                   class="btn btn--sm btn--outline">Edit</a>
                                <form method="post" action="<?= SITE_URL ?>/delete.php"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete \'<?= e(addslashes($f['title'])) ?>\'? This cannot be undone.')">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="id"         value="<?= (int) $f['id'] ?>">
                                    <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?= renderPagination($total, ADMIN_FILES_PER_PAGE, $page, $baseUrl) ?>
        <?php endif; ?>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
