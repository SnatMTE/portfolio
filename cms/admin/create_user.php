<?php
/**
 * cms/admin/create_user.php  —  Create a new CMS user
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$db     = getCMSDB();
$errors = [];

$roles = $db->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';
        $roleId   = (int) ($_POST['role_id'] ?? 3);

        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 40) {
            $errors[] = 'Username must be 3–40 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            $errors[] = 'Username may only contain letters, numbers, hyphens, and underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Check uniqueness
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $stmt->execute([':u' => $username, ':e' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email is already in use.';
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO users (username, email, password_hash, role_id)
                     VALUES (:username, :email, :password_hash, :role_id)"
                );
                $stmt->execute([
                    ':username'      => $username,
                    ':email'         => $email,
                    ':password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                    ':role_id'       => $roleId,
                ]);
                cmsFlashMessage('User created successfully.', 'success');
                redirect(SITE_URL . '/admin/users.php');
            }
        }
    }
}

$pageTitle = 'Create User';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<div class="page-header">
    <h1>Create User</h1>
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
                   value="<?= e($_POST['username'] ?? '') ?>"
                   maxlength="40" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   maxlength="255" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   minlength="8" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm">Confirm Password</label>
            <input type="password" id="confirm" name="confirm"
                   minlength="8" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="role_id">Role</label>
            <select id="role_id" name="role_id">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>"
                        <?= ((int)($_POST['role_id'] ?? 3) === (int) $role['id']) ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Create User</button>
        </div>
    </form>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
