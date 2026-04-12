<?php
/**
 * admin/posts.php
 *
 * Moderate posts: view recent posts and permanently delete them.
 *
 * Actions (POST):
 *   delete - Permanently delete a post (hard delete from DB)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $postId = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $postId > 0) {
        // Hard delete from the admin panel
        $db->prepare("DELETE FROM posts WHERE id = :id")->execute([':id' => $postId]);
        flashMessage('Post deleted.', 'success');
    }

    redirect(SITE_URL . '/admin/posts.php');
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage     = 30;
$offset      = ($currentPage - 1) * $perPage;
$totalPosts  = (int) $db->query("SELECT COUNT(*) FROM posts WHERE is_deleted = 0")->fetchColumn();

$stmt = $db->prepare(
    "SELECT p.id, p.content, p.created_at, p.is_deleted,
            u.username AS author_name, u.id AS author_id,
            t.id AS thread_id, t.title AS thread_title
     FROM posts p
     JOIN users u   ON u.id = p.user_id
     JOIN threads t ON t.id = p.thread_id
     WHERE p.is_deleted = 0
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute([':limit' => $perPage, ':offset' => $offset]);
$posts = $stmt->fetchAll();

$pageTitle       = 'Moderate Posts';
$activeAdminPage = 'posts';
require_once dirname(__DIR__) . '/templates/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Posts</h1>
            <span class="text-muted"><?= number_format($totalPosts) ?> visible posts</span>
        </div>

        <?php if (empty($posts)): ?>
            <p class="text-muted">No posts yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Excerpt</th>
                        <th>Author</th>
                        <th>Thread</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $p['thread_id'] ?>#post-<?= (int) $p['id'] ?>">
                                    <?= e(truncate($p['content'], 80)) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $p['author_id'] ?>">
                                    <?= e($p['author_name']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $p['thread_id'] ?>">
                                    <?= e(truncate($p['thread_title'], 50)) ?>
                                </a>
                            </td>
                            <td><?= e(formatDate($p['created_at'])) ?></td>
                            <td>
                                <form method="post" action="<?= SITE_URL ?>/admin/posts.php"
                                      onsubmit="return confirm('Permanently delete this post?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action"     value="delete">
                                    <input type="hidden" name="id"         value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?= renderPagination($totalPosts, $perPage, $currentPage, SITE_URL . '/admin/posts.php') ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>
