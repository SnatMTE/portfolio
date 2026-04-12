<?php
/**
 * admin/posts.php
 *
 * Lists all blog posts (published and draft) in a sortable table.
 * Provides Edit and Delete actions for each post.
 *
 * Delete action:
 *   Accepts a GET request with ?action=delete&id=NNN&csrf=TOKEN.
 *   Validates the CSRF token, then permanently removes the post and its
 *   associated post_tags rows (handled by ON DELETE CASCADE in the schema).
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';

// ---------------------------------------------------------------------------
// Handle delete action
// ---------------------------------------------------------------------------

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $deleteId    = (int) ($_GET['id'] ?? 0);
    $csrfToken   = trim($_GET['csrf'] ?? '');

    if ($deleteId > 0 && validateCsrf($csrfToken)) {
        $stmt = getDB()->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
        flashMessage('Post deleted successfully.', 'success');
    } else {
        flashMessage('Could not delete post. Invalid request.', 'error');
    }

    redirect(SITE_URL . '/admin/posts.php');
}

// ---------------------------------------------------------------------------
// Fetch all posts
// ---------------------------------------------------------------------------

/**
 * Retrieves all posts (published and draft) ordered by most recently updated.
 *
 * Joins users and categories so each row includes readable author and
 * category names without additional queries.
 *
 * @return array<int, array<string, mixed>>  Flat array of all post rows.
 */
function getAllPostsAdmin(): array
{
    return getDB()->query("
        SELECT p.id, p.title, p.slug, p.status, p.created_at, p.updated_at,
               u.username AS author_name,
               c.name     AS category_name
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        ORDER BY p.updated_at DESC
    ")->fetchAll();
}

$posts            = getAllPostsAdmin();
$currentAdminPage = 'posts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Posts – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>All Posts</h1>
            <a href="<?= SITE_URL ?>/admin/create_post.php" class="btn btn--primary btn--sm">+ New Post</a>
        </div>

        <?php renderFlash(); ?>

        <?php if (empty($posts)): ?>
            <p style="color:var(--clr-text-muted);">No posts yet. <a href="<?= SITE_URL ?>/admin/create_post.php">Create your first post →</a></p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/post/<?= e($post['slug']) ?>" target="_blank">
                                    <?= e($post['title']) ?>
                                </a>
                            </td>
                            <td><?= e($post['category_name'] ?? '—') ?></td>
                            <td><?= e($post['author_name'] ?? '—') ?></td>
                            <td>
                                <span class="badge badge--<?= e($post['status']) ?>">
                                    <?= e($post['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($post['created_at']) ?></td>
                            <td style="white-space:nowrap;display:flex;gap:.4rem;">
                                <a href="<?= SITE_URL ?>/admin/edit_post.php?id=<?= (int)$post['id'] ?>"
                                   class="btn btn--sm btn--outline">Edit</a>
                                <a href="<?= SITE_URL ?>/admin/posts.php?action=delete&id=<?= (int)$post['id'] ?>&csrf=<?= csrfToken() ?>"
                                   class="btn btn--sm btn--danger"
                                   data-confirm="Delete &quot;<?= e(addslashes($post['title'])) ?>&quot;? This cannot be undone.">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
