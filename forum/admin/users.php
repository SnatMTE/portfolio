<?php
/**
 * admin/users.php
 *
 * Manage forum members: view all users, promote/demote between admin and user,
 * and delete accounts.
 *
 * Actions (POST):
 *   promote  - Set user role to admin
 *   demote   - Set user role to user
 *   delete   - Delete user account (cascades to threads and posts)
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireAdminAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    // Prevent self-modification
    if ($userId > 0 && $userId !== (int) currentUser()['id']) {
        if ($action === 'promote') {
            $db->prepare("UPDATE users SET role_id = 1 WHERE id = :id")->execute([':id' => $userId]);
            flashMessage('User promoted to admin.', 'success');
        } elseif ($action === 'demote') {
            $db->prepare("UPDATE users SET role_id = 2 WHERE id = :id")->execute([':id' => $userId]);
            flashMessage('User demoted to standard user.', 'success');
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);
            flashMessage('User account deleted.', 'success');
        }
    } elseif ($userId === (int) currentUser()['id']) {
        flashMessage('You cannot modify your own account from this panel.', 'error');
    }

    redirect(SITE_URL . '/admin/users.php');
}

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage     = 30;
$totalUsers  = countUsers();
$users       = getAllUsers($currentPage, $perPage);

$pageTitle       = 'Manage Users';
$activeAdminPage = 'users';
require_once dirname(__DIR__) . '/templates/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Users</h1>
            <span class="text-muted"><?= number_format($totalUsers) ?> members</span>
        </div>

        <?php if (empty($users)): ?>
            <p class="text-muted">No users yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $isSelf = (int) $u['id'] === (int) currentUser()['id'];
                    ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/profile.php?id=<?= (int) $u['id'] ?>">
                                    <?= e($u['username']) ?>
                                </a>
                                <?php if ($isSelf): ?>
                                    <small class="text-muted">(you)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="badge badge--role"><?= e($u['role']) ?></span></td>
                            <td><?= e(formatDate($u['created_at'])) ?></td>
                            <td>
                                <?php if (!$isSelf): ?>
                                    <form method="post" action="<?= SITE_URL ?>/admin/users.php"
                                          style="display:flex;gap:.4rem;flex-wrap:wrap">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="user_id"    value="<?= (int) $u['id'] ?>">
                                        <?php if ($u['role'] === 'user'): ?>
                                            <button name="action" value="promote"
                                                    class="btn btn--outline btn--sm">Promote</button>
                                        <?php else: ?>
                                            <button name="action" value="demote"
                                                    class="btn btn--outline btn--sm">Demote</button>
                                        <?php endif; ?>
                                        <button name="action" value="delete"
                                                class="btn btn--danger btn--sm"
                                                onclick="return confirm('Delete this user and all their content?')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?= renderPagination($totalUsers, $perPage, $currentPage, SITE_URL . '/admin/users.php') ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>
