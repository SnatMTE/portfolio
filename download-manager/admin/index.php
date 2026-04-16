<?php
/**
 * admin/index.php
 *
 * Admin dashboard. Shows at-a-glance statistics and recent uploads.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/core/file_helper.php';

requireLogin();

$db    = getDB();
$admin = currentAdminUser();

// Prune stale tokens in the background on each dashboard visit
pruneExpiredTokens();

// Aggregate stats
$totalFiles      = (int) $db->query("SELECT COUNT(*) FROM dm_downloads")->fetchColumn();
$publicFiles     = (int) $db->query("SELECT COUNT(*) FROM dm_downloads WHERE visibility = 'public'")->fetchColumn();
$privateFiles    = $totalFiles - $publicFiles;
$totalDownloads  = (int) $db->query("SELECT COALESCE(SUM(download_count), 0) FROM dm_downloads")->fetchColumn();

// Five most recently added files
$recentFiles = $db->query(
    "SELECT id, title, original_name, file_size, mime_type, visibility, download_count, created_at
     FROM dm_downloads
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

$currentAdminPage = 'dashboard';

echo '<!DOCTYPE html><html lang="en"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Dashboard – ' . e(SITE_NAME) . '</title>';
echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/style.css">';
echo '</head><body>';
?>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <span>Welcome back, <strong><?= e($admin['username'] ?? 'Admin') ?></strong></span>
        </div>

        <?php renderFlash(); ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($totalFiles) ?></span>
                <div class="stat-card__label">Total Files</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($publicFiles) ?></span>
                <div class="stat-card__label">Public Files</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($privateFiles) ?></span>
                <div class="stat-card__label">Private Files</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($totalDownloads) ?></span>
                <div class="stat-card__label">Total Downloads</div>
            </div>
        </div>

        <!-- Recent uploads -->
        <div class="admin-header" style="margin-top:1.5rem;">
            <h2 style="font-size:1.1rem;">Recent Uploads</h2>
            <a href="<?= SITE_URL ?>/upload.php" class="btn btn--primary btn--sm">+ Upload File</a>
        </div>

        <?php if (empty($recentFiles)): ?>
            <p style="color:var(--clr-text-muted);">
                No files yet. <a href="<?= SITE_URL ?>/upload.php">Upload your first file →</a>
            </p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                        <th>Visibility</th>
                        <th>Downloads</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFiles as $f): ?>
                        <tr>
                            <td>
                                <span style="margin-right:.5rem;" aria-hidden="true">
                                    <?= fileTypeIcon($f['mime_type']) ?>
                                </span>
                                <a href="<?= SITE_URL ?>/download.php?id=<?= (int) $f['id'] ?>">
                                    <?= e($f['title']) ?>
                                </a>
                                <br><small style="color:var(--clr-text-muted)"><?= e($f['original_name']) ?></small>
                            </td>
                            <td><?= e(formatFileSize((int) $f['file_size'])) ?></td>
                            <td>
                                <span class="badge badge--<?= e($f['visibility']) ?>">
                                    <?= e($f['visibility']) ?>
                                </span>
                            </td>
                            <td><?= number_format((int) $f['download_count']) ?></td>
                            <td><?= e(formatDate($f['created_at'])) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/edit.php?id=<?= (int) $f['id'] ?>"
                                   class="btn btn--sm btn--outline">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Quick links -->
        <div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/admin/files.php" class="btn btn--outline">Manage All Files</a>
            <a href="<?= SITE_URL ?>/upload.php"      class="btn btn--outline">Upload New File</a>
            <a href="<?= SITE_URL ?>"                  class="btn btn--outline">View Public Listing</a>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
