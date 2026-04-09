<?php
/**
 * profile.php
 *
 * Displays a user's public profile: bio, join date, post count, and recent threads.
 * URL: profile.php?id={userId}
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

$userId = (int) ($_GET['id'] ?? 0);
if ($userId === 0) {
    redirect(SITE_URL . '/');
}

$profileUser = getUserById($userId);
if ($profileUser === null) {
    http_response_code(404);
    $pageTitle = 'User Not Found';
    require_once __DIR__ . '/templates/header.php';
    echo '<div class="empty-state"><h1>User not found.</h1><a href="' . SITE_URL . '/" class="btn btn--primary">Back to homepage</a></div>';
    require_once __DIR__ . '/templates/footer.php';
    exit;
}

// Fetch recent threads by this user
$stmt = getDB()->prepare(
    "SELECT t.id, t.title, t.created_at, c.name AS category_name, c.slug AS category_slug
     FROM threads t
     JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = :uid
     ORDER BY t.created_at DESC
     LIMIT 10"
);
$stmt->execute([':uid' => $userId]);
$recentThreads = $stmt->fetchAll();

$postCount   = getUserPostCount($userId);
$threadCount = getUserThreadCount($userId);

$pageTitle = $profileUser['username'] . "'s Profile";
require_once __DIR__ . '/templates/header.php';
?>

<div class="profile-layout">
    <aside class="profile-sidebar">
        <div class="profile-card">
            <div class="profile-card__avatar" aria-hidden="true">
                <img src="<?= e(gravatar_url($profileUser['email'] ?? '', 72)) ?>"
                     alt="<?= e($profileUser['username']) ?>'s avatar" width="72" height="72">
            </div>
            <h1 class="profile-card__username"><?= e($profileUser['username']) ?></h1>
            <span class="badge badge--role"><?= e($profileUser['role']) ?></span>

            <?php if (!empty($profileUser['bio'])): ?>
                <p class="profile-card__bio"><?= e($profileUser['bio']) ?></p>
            <?php endif; ?>

            <dl class="profile-card__stats">
                <div class="profile-card__stat">
                    <dt>Joined</dt>
                    <dd><?= e(formatDate($profileUser['created_at'])) ?></dd>
                </div>
                <div class="profile-card__stat">
                    <dt>Threads</dt>
                    <dd><?= number_format($threadCount) ?></dd>
                </div>
                <div class="profile-card__stat">
                    <dt>Posts</dt>
                    <dd><?= number_format($postCount) ?></dd>
                </div>
            </dl>
        </div>
    </aside>

    <div class="profile-main">
        <h2 class="page-heading">Recent Threads</h2>

        <?php if (empty($recentThreads)): ?>
            <div class="empty-state"><p>This user has not started any threads yet.</p></div>
        <?php else: ?>
            <div class="thread-list">
                <?php foreach ($recentThreads as $thread): ?>
                    <article class="thread-row">
                        <div class="thread-row__flags"></div>
                        <div class="thread-row__main">
                            <h3 class="thread-row__title">
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $thread['id'] ?>">
                                    <?= e($thread['title']) ?>
                                </a>
                            </h3>
                            <div class="thread-row__meta">
                                In
                                <a href="<?= SITE_URL ?>/category.php?slug=<?= e($thread['category_slug']) ?>">
                                    <?= e($thread['category_name']) ?>
                                </a>
                                &middot; <?= e(formatDate($thread['created_at'])) ?>
                            </div>
                        </div>
                        <div class="thread-row__stats"></div>
                        <div class="thread-row__last"></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
