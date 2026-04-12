<?php
/**
 * calendar/login.php
 *
 * Admin login page for standalone mode.
 * In CMS mode, this page redirects to the shared CMS login.
 *
 * Security measures:
 *   - CSRF token on the form
 *   - password_verify() for credential checking
 *   - Generic error message
 *   - Session regeneration on successful login
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// In CMS mode, defer to the CMS login page
if (defined('CMS_ROOT')) {
    $cmsLogin = defined('CMS_URL') ? CMS_URL . '/login.php' : '../login.php';
    redirect($cmsLogin);
}

// Already logged in
if (!empty($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = trim($_POST['csrf_token'] ?? '');

    if (!validateCsrf($submitted)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            $stmt = getDB()->prepare(
                "SELECT id, username, password FROM users WHERE username = :username LIMIT 1"
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                redirect(SITE_URL . '/admin/');
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

$pageTitle = 'Login';
$metaDesc  = 'Sign in to the Calendar admin panel.';
require_once __DIR__ . '/templates/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>&#128274; Admin Login</h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username"
                       class="form-input"
                       autocomplete="username"
                       required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-input"
                       autocomplete="current-password"
                       required>
            </div>

            <button type="submit" class="btn btn--primary btn--full">Sign In</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
