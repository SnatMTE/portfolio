<?php
/**
 * crm/login.php
 *
 * Standalone CRM login page.
 * Only used when the CRM runs without a parent CMS installation.
 * Authenticates against the crm_users table in the CRM database.
 */

require_once __DIR__ . '/config.php';

// Already logged in — go straight to the dashboard.
if (cmsIsLoggedIn()) {
    header('Location: ' . CRM_URL . '/');
    exit;
}

$error  = '';
$next   = $_GET['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF before doing anything else.
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter your username and password.';
        } else {
            $stmt = getCRMDB()->prepare(
                "SELECT u.id, u.username, u.email, u.password_hash, r.name AS role
                 FROM crm_users u
                 JOIN crm_roles r ON r.id = u.role_id
                 WHERE u.username = :username"
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate the session ID to prevent fixation.
                session_regenerate_id(true);
                $_SESSION['crm_user_id'] = (int) $user['id'];
                $_SESSION['crm_username'] = $user['username'];
                $_SESSION['crm_role']    = $user['role'];

                // Redirect back to the intended page, or the dashboard.
                $dest = ($next !== '') ? urldecode($next) : CRM_URL . '/';
                // Reject open redirects — only allow paths under CRM_URL.
                if (!str_starts_with($dest, CRM_URL)) {
                    $dest = CRM_URL . '/';
                }
                header('Location: ' . $dest);
                exit;
            }

            // Deliberate vague message to avoid username enumeration.
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?= CRM_NAME ?></title>
    <link rel="stylesheet" href="<?= e(CRM_URL) ?>/assets/css/crm.css">
    <style>
        body           { font-family: system-ui, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card    { background: #fff; border-radius: .75rem; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 2.5rem 2rem; width: 100%; max-width: 380px; }
        .login-card h1 { margin: 0 0 1.5rem; font-size: 1.5rem; color: #1e293b; }
        .form-group    { margin-bottom: 1rem; }
        label          { display: block; font-size: .875rem; font-weight: 500; color: #374151; margin-bottom: .35rem; }
        input[type="text"], input[type="password"] { width: 100%; box-sizing: border-box; padding: .6rem .75rem; border: 1px solid #d1d5db; border-radius: .375rem; font-size: 1rem; }
        input:focus    { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,.15); }
        .btn-login     { width: 100%; padding: .7rem; background: #f97316; color: #fff; border: none; border-radius: .375rem; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn-login:hover { background: #ea6c0a; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; border-radius: .375rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; }
        .setup-link    { margin-top: 1.25rem; text-align: center; font-size: .8rem; color: #6b7280; }
        .setup-link a  { color: #f97316; }
    </style>
</head>
<body>
<div class="login-card">
    <h1>&#128200; <?= e(CRM_NAME) ?></h1>

    <?php if ($error !== ''): ?>
        <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(CRM_URL) ?>/login.php<?= $next !== '' ? '?next=' . e(urlencode($next)) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(cmsCsrfToken()) ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= e($_POST['username'] ?? '') ?>" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login">Sign in</button>
    </form>

    <p class="setup-link">First time? <a href="<?= e(CRM_URL) ?>/setup.php">Run setup</a></p>
</div>
</body>
</html>
