<?php
/**
 * admin/index.php
 *
 * Admin dashboard - shows summary statistics and recent activity.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$stats = getAdminStats();

// Recent threads
$recentThreads = getDB()->query(
    "SELECT t.id, t.title, t.created_at, u.username AS author_name, c.name AS category_name
     FROM threads t
     JOIN users u      ON u.id = t.user_id
     JOIN categories c ON c.id = t.category_id
     ORDER BY t.created_at DESC
     LIMIT 10"
)->fetchAll();

// Recent users
$recentUsers = getDB()->query(
    "SELECT u.id, u.username, u.email, u.created_at, r.name AS role
     FROM users u
     JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC
     LIMIT 5"
)->fetchAll();

$pageTitle       = 'Admin Dashboard';
$activeAdminPage = 'dashboard';
require_once dirname(__DIR__) . '/templates/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Dashboard</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-card__number"><?= $stats['categories'] ?></span>
                <span class="stat-card__label">Categories</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($stats['threads']) ?></span>
                <span class="stat-card__label">Threads</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($stats['posts']) ?></span>
                <span class="stat-card__label">Posts</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= number_format($stats['users']) ?></span>
                <span class="stat-card__label">Members</span>
            </div>
        </div>

        <h2 class="admin-section-title">Recent Threads</h2>
        <?php if (empty($recentThreads)): ?>
            <p class="text-muted">No threads yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentThreads as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int) $t['id'] ?>">
                                    <?= e($t['title']) ?>
                                </a>
                            </td>
                            <td><?= e($t['category_name']) ?></td>
                            <td><?= e($t['author_name']) ?></td>
                            <td><?= e(formatDate($t['created_at'])) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/threads.php?action=edit&id=<?= (int) $t['id'] ?>"
                                   class="btn btn--outline btn--sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 class="admin-section-title" style="margin-top:2rem">Recent Members</h2>
        <?php if (empty($recentUsers)): ?>
            <p class="text-muted">No users yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $u['id'] ?>">
                                    <?= e($u['username']) ?>
                                </a>
                            </td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge badge--role"><?= e($u['role']) ?></span></td>
                            <td><?= e(formatDate($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>
