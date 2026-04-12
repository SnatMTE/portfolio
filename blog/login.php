<?php
/**
 * login.php
 *
 * Admin login page. Presents a login form and processes POST credentials.
 *
 * Security measures:
 *   - CSRF token on the form
 *   - password_verify() for credential checking
 *   - Generic error message (does not reveal whether username or password is wrong)
 *   - Session regeneration on successful login to prevent session fixation
 *   - Redirects to admin dashboard on success
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// When inside CMS, redirect to the shared CMS login page
if (defined('CMS_ROOT')) {
    $cmsLogin = defined('CMS_URL') ? CMS_URL . '/login.php' : '../login.php';
    header('Location: ' . $cmsLogin);
    exit;
}

// Already logged in – redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    $submittedToken = trim($_POST['csrf_token'] ?? '');

    if (!validateCsrf($submittedToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            $stmt = getDB()->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                redirect(SITE_URL . '/admin/');
            } else {
                // Generic message – do not reveal whether username or password was wrong
                $error = 'Invalid username or password.';
            }
        }
    }
}

$pageTitle = 'Admin Login';
$metaDesc  = 'Sign in to the admin panel.';
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
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    class="form-control"
                    autocomplete="username"
                    maxlength="80"
                    required
                    value="<?= e($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn--primary btn--full">Log in</button>
        </form>

        <p style="text-align:center;margin-top:1rem;font-size:.85rem;">
            <a href="<?= SITE_URL ?>">← Back to site</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
