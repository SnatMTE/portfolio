<?php
/**
 * cms/admin/edit_user.php  —  Edit an existing CMS user
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$db     = getCMSDB();
$errors = [];
$userId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$editUser = $stmt->fetch();

if (!$editUser) {
    cmsFlashMessage('User not found.', 'error');
    redirect(SITE_URL . '/admin/users.php');
}

$roles = $db->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';
        $roleId   = (int) ($_POST['role_id'] ?? $editUser['role_id']);

        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 40) {
            $errors[] = 'Username must be 3–40 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, hyphens, and underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password !== '' && mb_strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($password !== '' && $password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Uniqueness check (excluding current user)
            $stmt = $db->prepare(
                "SELECT id FROM users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1"
            );
            $stmt->execute([':u' => $username, ':e' => $email, ':id' => $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email is already in use by another account.';
            } else {
                if ($password !== '') {
                    $stmt = $db->prepare(
                        "UPDATE users SET username = :username, email = :email,
                         password_hash = :password_hash, role_id = :role_id WHERE id = :id"
                    );
                    $stmt->execute([
                        ':username'      => $username,
                        ':email'         => $email,
                        ':password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                        ':role_id'       => $roleId,
                        ':id'            => $userId,
                    ]);
                } else {
                    $stmt = $db->prepare(
                        "UPDATE users SET username = :username, email = :email,
                         role_id = :role_id WHERE id = :id"
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':email'    => $email,
                        ':role_id'  => $roleId,
                        ':id'       => $userId,
                    ]);
                }
                // Update session if editing own account
                if ($userId === (int) $_SESSION['user_id']) {
                    $_SESSION['username']       = $username;
                    $_SESSION['admin_username'] = $username;
                }
                cmsFlashMessage('User updated successfully.', 'success');
                redirect(SITE_URL . '/admin/users.php');
            }
        }
    }
}

// Pre-fill form with current values (or POST values on error)
$formUsername = $_POST['username'] ?? $editUser['username'];
$formEmail    = $_POST['email']    ?? $editUser['email'];
$formRoleId   = (int) ($_POST['role_id'] ?? $editUser['role_id']);

$pageTitle = 'Edit User';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<div class="page-header">
    <h1>Edit User: <?= e($editUser['username']) ?></h1>
    <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn--secondary">&larr; Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert--error">
        <ul class="error-list">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= e($formUsername) ?>" maxlength="40" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= e($formEmail) ?>" maxlength="255" required>
        </div>

        <div class="form-group">
            <label for="password">New Password <span class="hint">(leave blank to keep current)</span></label>
            <input type="password" id="password" name="password"
                   minlength="8" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm">Confirm New Password</label>
            <input type="password" id="confirm" name="confirm"
                   minlength="8" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="role_id">Role</label>
            <select id="role_id" name="role_id">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>"
                        <?= ($formRoleId === (int) $role['id']) ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
