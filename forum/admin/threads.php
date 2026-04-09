<?php
/**
 * admin/threads.php
 *
 * Moderate threads: toggle sticky/locked status, delete threads.
 * Also handles quick moderation form submissions from thread.php.
 *
 * Actions (POST):
 *   toggle_sticky  - Toggle sticky status
 *   toggle_lock    - Toggle locked status
 *   delete         - Delete thread (cascades to posts)
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action   = $_POST['action']    ?? '';
    $threadId = (int) ($_POST['thread_id'] ?? 0);

    if ($threadId > 0) {
        if ($action === 'toggle_sticky') {
            $db->prepare("UPDATE threads SET is_sticky = NOT is_sticky WHERE id = :id")
               ->execute([':id' => $threadId]);
            flashMessage('Thread sticky status updated.', 'success');
        } elseif ($action === 'toggle_lock') {
            $db->prepare("UPDATE threads SET is_locked = NOT is_locked WHERE id = :id")
               ->execute([':id' => $threadId]);
            flashMessage('Thread lock status updated.', 'success');
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM threads WHERE id = :id")->execute([':id' => $threadId]);
            flashMessage('Thread deleted.', 'success');
            redirect(SITE_URL . '/admin/threads.php');
        }
    }

    // Redirect back to the thread if a thread_id is known, otherwise to the list
    $returnUrl = isset($_POST['return_url']) ? filter_var($_POST['return_url'], FILTER_VALIDATE_URL) : false;
    if ($returnUrl && str_starts_with($returnUrl, SITE_URL)) {
        redirect($returnUrl);
    }
    redirect(SITE_URL . '/admin/threads.php');
}

$currentPage  = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 30;
$offset       = ($currentPage - 1) * $perPage;
$totalThreads = (int) $db->query("SELECT COUNT(*) FROM threads")->fetchColumn();

$stmt = $db->prepare(
    "SELECT t.id, t.title, t.created_at, t.is_sticky, t.is_locked, t.view_count,
            u.username AS author_name, c.name AS category_name, c.slug AS category_slug,
            COUNT(p.id) AS post_count
     FROM threads t
     JOIN users u      ON u.id = t.user_id
     JOIN categories c ON c.id = t.category_id
     LEFT JOIN posts p ON p.thread_id = t.id AND p.is_deleted = 0
     GROUP BY t.id
     ORDER BY t.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->execute([':limit' => $perPage, ':offset' => $offset]);
$threads = $stmt->fetchAll();

$pageTitle       = 'Moderate Threads';
$activeAdminPage = 'threads';
require_once dirname(__DIR__) . '/templates/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Threads</h1>
            <span class="text-muted"><?= number_format($totalThreads) ?> total</span>
        </div>

        <?php if (empty($threads)): ?>
            <p class="text-muted">No threads yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Posts</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($threads as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $t['id'] ?>">
                                    <?= e($t['title']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/category.php?slug=<?= e($t['category_slug']) ?>">
                                    <?= e($t['category_name']) ?>
                                </a>
                            </td>
                            <td><?= e($t['author_name']) ?></td>
                            <td><?= number_format((int) $t['post_count']) ?></td>
                            <td><?= number_format((int) $t['view_count']) ?></td>
                            <td>
                                <?php if ($t['is_sticky']): ?>
                                    <span class="badge badge--sticky">Sticky</span>
                                <?php endif; ?>
                                <?php if ($t['is_locked']): ?>
                                    <span class="badge badge--locked">Locked</span>
                                <?php endif; ?>
                                <?php if (!$t['is_sticky'] && !$t['is_locked']): ?>
                                    <span class="text-muted">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(formatDate($t['created_at'])) ?></td>
                            <td>
                                <form method="post" action="<?= SITE_URL ?>/admin/threads.php"
                                      style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="thread_id"  value="<?= (int) $t['id'] ?>">
                                    <button name="action" value="toggle_sticky"
                                            class="btn btn--outline btn--sm">
                                        <?= $t['is_sticky'] ? 'Unpin' : 'Pin' ?>
                                    </button>
                                    <button name="action" value="toggle_lock"
                                            class="btn btn--outline btn--sm">
                                        <?= $t['is_locked'] ? 'Unlock' : 'Lock' ?>
                                    </button>
                                    <button name="action" value="delete"
                                            class="btn btn--danger btn--sm"
                                            onclick="return confirm('Delete this thread and all its posts?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?= renderPagination($totalThreads, $perPage, $currentPage, SITE_URL . '/admin/threads.php') ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>
