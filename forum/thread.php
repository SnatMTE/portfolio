<?php
/**
 * thread.php
 *
 * Displays a thread with all its posts and a reply form for logged-in users.
 * Also handles POST actions: submit a reply, soft-delete a post.
 *
 * URL: thread.php?id={threadId}
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$threadId = (int) ($_GET['id'] ?? 0);
if ($threadId === 0) {
    redirect(SITE_URL . '/');
}

$thread = getThreadById($threadId);
if ($thread === null) {
    http_response_code(404);
    $pageTitle = 'Thread Not Found';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="empty-state"><h1>Thread not found.</h1><a href="' . SITE_URL . '/" class="btn btn--primary">Back to homepage</a></div>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Handle POST actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    // --- Submit reply ---
    if ($action === 'reply') {
        if ((int) $thread['is_locked'] && !isAdmin()) {
            flashMessage('This thread is locked and cannot accept new replies.', 'error');
            redirect(SITE_URL . '/thread.php?id=' . $threadId);
        }

        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            flashMessage('Reply content cannot be empty.', 'error');
            redirect(SITE_URL . '/thread.php?id=' . $threadId);
        }
        if (mb_strlen($content) > 10000) {
            flashMessage('Reply is too long (maximum 10,000 characters).', 'error');
            redirect(SITE_URL . '/thread.php?id=' . $threadId);
        }

        $postId = createPost($threadId, (int) currentUser()['id'], $content);
        $total  = countPostsByThread($threadId);
        $lastPage = max(1, (int) ceil($total / POSTS_PER_PAGE));
        flashMessage('Your reply has been posted.', 'success');
        redirect(SITE_URL . '/thread.php?id=' . $threadId . '&page=' . $lastPage . '#post-' . $postId);
    }

    // --- Delete post ---
    if ($action === 'delete_post') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            // Verify ownership or admin
            $stmt = getDB()->prepare("SELECT user_id FROM posts WHERE id = :id AND is_deleted = 0");
            $stmt->execute([':id' => $postId]);
            $row = $stmt->fetch();
            if ($row && ((int) $row['user_id'] === (int) currentUser()['id'] || isAdmin())) {
                $stmt = getDB()->prepare("UPDATE posts SET is_deleted = 1, updated_at = datetime('now') WHERE id = :id");
                $stmt->execute([':id' => $postId]);
                flashMessage('Post deleted.', 'success');
            } else {
                flashMessage('You do not have permission to delete that post.', 'error');
            }
        }
        redirect(SITE_URL . '/thread.php?id=' . $threadId);
    }

    redirect(SITE_URL . '/thread.php?id=' . $threadId);
}

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
incrementThreadViews($threadId);

$currentPage  = max(1, (int) ($_GET['page'] ?? 1));
$totalPosts   = countPostsByThread($threadId);
$posts        = getPostsByThread($threadId, $currentPage, POSTS_PER_PAGE);
$threadOwnerId = (int) $thread['user_id'];
$postNumber   = ($currentPage - 1) * POSTS_PER_PAGE;

$pageTitle = $thread['title'];
$metaDesc  = truncate($posts[0]['content'] ?? '', 160);

require_once __DIR__ . '/templates/header.php';
?>

<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
        <li><a href="<?= SITE_URL ?>">Home</a></li>
        <li>
            <a href="<?= SITE_URL ?>/category.php?slug=<?= e($thread['category_slug']) ?>">
                <?= e($thread['category_name']) ?>
            </a>
        </li>
        <li aria-current="page"><?= e($thread['title']) ?></li>
    </ol>
</nav>

<div class="thread-view-header">
    <div class="thread-view-header__info">
        <h1 class="thread-view-header__title"><?= e($thread['title']) ?></h1>
        <div class="thread-view-header__meta">
            <span>Started by
                <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $thread['user_id'] ?>">
                    <?= e($thread['author_name']) ?>
                </a>
            </span>
            <span><?= e(formatDateTime($thread['created_at'])) ?></span>
            <span><?= number_format((int) $thread['view_count']) ?> views</span>
            <span><?= number_format($totalPosts) ?> posts</span>
            <?php if ($thread['is_sticky']): ?>
                <span class="badge badge--sticky">Sticky</span>
            <?php endif; ?>
            <?php if ($thread['is_locked']): ?>
                <span class="badge badge--locked">Locked</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (isAdmin()): ?>
        <div class="thread-view-header__admin">
            <form method="post" action="<?= SITE_URL ?>/admin/threads.php">
                <input type="hidden" name="thread_id"  value="<?= (int) $threadId ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button name="action" value="toggle_sticky"
                        class="btn btn--outline btn--sm">
                    <?= $thread['is_sticky'] ? 'Unpin' : 'Pin' ?>
                </button>
                <button name="action" value="toggle_lock"
                        class="btn btn--outline btn--sm">
                    <?= $thread['is_locked'] ? 'Unlock' : 'Lock' ?>
                </button>
                <button name="action" value="delete"
                        class="btn btn--danger btn--sm"
                        onclick="return confirm('Delete this entire thread?')">
                    Delete Thread
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state"><p>No posts found in this thread.</p></div>
<?php else: ?>
    <div class="post-list" aria-label="Posts in thread">
        <?php foreach ($posts as $post):
            $postNumber++;
            require __DIR__ . '/templates/post_item.php';
        endforeach; ?>
    </div>

    <?= renderPagination(
        $totalPosts,
        POSTS_PER_PAGE,
        $currentPage,
        SITE_URL . '/thread.php?id=' . $threadId
    ) ?>
<?php endif; ?>

<?php if (isLoggedIn() && !(int) $thread['is_locked']): ?>
    <section class="reply-section" id="reply">
        <h2 class="reply-section__title">Post a Reply</h2>
        <form method="post" action="<?= SITE_URL ?>/thread.php?id=<?= (int) $threadId ?>"
              class="reply-form">
            <input type="hidden" name="action"     value="reply">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-group">
                <label for="content" class="sr-only">Your reply</label>
                <textarea name="content" id="content" class="form-control reply-textarea"
                          placeholder="Write your reply..." rows="6" required
                          maxlength="10000"></textarea>
            </div>
            <button type="submit" class="btn btn--primary">Post Reply</button>
        </form>
    </section>
<?php elseif ((int) $thread['is_locked']): ?>
    <div class="alert alert--info">This thread is locked. No new replies can be posted.</div>
<?php elseif (!isLoggedIn()): ?>
    <div class="alert alert--info">
        <a href="<?= SITE_URL ?>/login.php">Log in</a> or
        <a href="<?= SITE_URL ?>/register.php">register</a> to reply.
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
