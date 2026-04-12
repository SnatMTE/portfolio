<?php
/**
 * admin/index.php
 *
 * Admin dashboard. Displays at-a-glance statistics:
 *   - Total published posts
 *   - Total draft posts
 *   - Total categories
 *   - Total tags
 *
 * Also lists the five most recently updated posts for quick access.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Gather dashboard statistics
// ---------------------------------------------------------------------------

$db = getDB();

/**
 * Counts the total number of posts with a given status.
 *
 * @param PDO    $db      Active database connection.
 * @param string $status  Either 'published' or 'draft'.
 *
 * @return int  Number of posts with the given status.
 */
function countPostsByStatus(PDO $db, string $status): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE status = :status");
    $stmt->execute([':status' => $status]);
    return (int) $stmt->fetchColumn();
}

$statsPublished  = countPostsByStatus($db, 'published');
$statsDraft      = countPostsByStatus($db, 'draft');
$statsCategories = (int) $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$statsTags       = (int) $db->query("SELECT COUNT(*) FROM tags")->fetchColumn();

// Five most recently modified posts for the "recent posts" table
$recentPosts = $db->query("
    SELECT p.id, p.title, p.slug, p.status, p.updated_at, u.username AS author_name
    FROM posts p
    LEFT JOIN users u ON u.id = p.author_id
    ORDER BY p.updated_at DESC
    LIMIT 5
")->fetchAll();

$admin = currentAdminUser();
$currentAdminPage = 'dashboard';

// Page header
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
                <span class="stat-card__number"><?= $statsPublished ?></span>
                <div class="stat-card__label">Published Posts</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $statsDraft ?></span>
                <div class="stat-card__label">Draft Posts</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $statsCategories ?></span>
                <div class="stat-card__label">Categories</div>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= $statsTags ?></span>
                <div class="stat-card__label">Tags</div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="admin-header" style="margin-top:1.5rem;">
            <h2 style="font-size:1.1rem;">Recent Posts</h2>
            <a href="<?= SITE_URL ?>/admin/create_post.php" class="btn btn--primary btn--sm">+ New Post</a>
        </div>

        <?php if (empty($recentPosts)): ?>
            <p style="color:var(--clr-text-muted);">No posts yet. <a href="<?= SITE_URL ?>/admin/create_post.php">Create your first post →</a></p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $p): ?>
                        <tr>
                            <td><a href="<?= SITE_URL ?>/post/<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></td>
                            <td><?= e($p['author_name'] ?? '-') ?></td>
                            <td><span class="badge badge--<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                            <td><?= formatDate($p['updated_at']) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/edit_post.php?id=<?= (int)$p['id'] ?>" class="btn btn--sm btn--outline">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body></html>
