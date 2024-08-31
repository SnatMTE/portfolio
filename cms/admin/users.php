<?php
/**
 * cms/admin/users.php  —  User Management
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$db    = getCMSDB();
$flash = cmsGetFlash();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!cmsValidateCsrf($token)) {
        cmsFlashMessage('Invalid security token.', 'error');
        redirect(SITE_URL . '/admin/users.php');
    }

    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId === (int) $_SESSION['user_id']) {
        cmsFlashMessage('You cannot delete your own account.', 'error');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $deleteId]);
        cmsFlashMessage('User deleted.', 'success');
    }
    redirect(SITE_URL . '/admin/users.php');
}

$users = $db->query(
    "SELECT u.id, u.username, u.email, u.created_at, r.name AS role
     FROM users u
     JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC"
)->fetchAll();

$pageTitle = 'Users';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Users</h1>
    <a href="<?= SITE_URL ?>/admin/create_user.php" class="btn btn--primary">+ New User</a>
</div>

<div class="card">
    <table class="data-table">
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
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge--<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                    <td><?= e(cmsFormatDate($u['created_at'])) ?></td>
                    <td class="actions">
                        <a href="<?= SITE_URL ?>/admin/edit_user.php?id=<?= (int) $u['id'] ?>" class="btn btn--sm">Edit</a>
                        <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                            <form method="post" action="" style="display:inline"
                                  onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">
                                <input type="hidden" name="delete_id"  value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn--sm btn--danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="empty">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
