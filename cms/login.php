<?php
/**
 * cms/login.php
 *
 * Unified CMS login page.
 * Handles authentication for the CMS and all installed modules.
 *
 * On success sets:
 *   $_SESSION['user_id']       – integer user ID
 *   $_SESSION['admin_id']      – same (blog/store compat)
 *   $_SESSION['username']      – username string
 *   $_SESSION['admin_username']– same (blog compat)
 *   $_SESSION['role']          – role name: 'admin'|'editor'|'user'
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Already logged in
if (cmsIsLoggedIn()) {
    redirect(SITE_URL . '/admin/');
}

$errors   = [];
$formLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $formLogin = trim($_POST['login']    ?? '');
        $password  = $_POST['password'] ?? '';

        if ($formLogin === '') {
            $errors[] = 'Please enter your username or email.';
        }
        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        if (empty($errors)) {
            $stmt = getCMSDB()->prepare(
                "SELECT u.id, u.username, u.password_hash, r.name AS role
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE u.username = :login OR u.email = :login
                 LIMIT 1"
            );
            $stmt->execute([':login' => $formLogin]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                // Set all session variables needed by CMS and all modules
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['admin_id']       = $user['id'];    // blog/store compat
                $_SESSION['username']       = $user['username'];
                $_SESSION['admin_username'] = $user['username']; // blog compat
                $_SESSION['role']           = $user['role'];

                cmsFlashMessage('Welcome back, ' . $user['username'] . '!', 'success');

                // Redirect to intended destination or admin dashboard
                $next = filter_var($_GET['next'] ?? '', FILTER_SANITIZE_URL);
                if ($next && str_starts_with($next, '/')) {
                    redirect($next);
                }
                redirect(SITE_URL . '/admin/');
            } else {
                $errors[] = 'Invalid username/email or password.';
            }
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>&#128274; Sign In</h1>
        <p style="text-align:center;color:var(--color-muted);margin-bottom:1.5rem;font-size:.875rem;">
            <?= e(getSetting('site_name', CMS_NAME)) ?>
        </p>

        <?php if ($errors): ?>
            <div class="alert alert--error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">

            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login"
                       value="<?= e($formLogin) ?>"
                       maxlength="255" required autofocus
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <div class="form-actions" style="border:none;margin-top:1rem;padding-top:0">
                <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center">
                    Sign In
                </button>
            </div>
        </form>

        <p style="text-align:center;margin-top:1rem;font-size:.8rem;color:var(--color-muted)">
            No account? Run
            <a href="<?= SITE_URL ?>/setup.php">setup.php</a> to create one.
        </p>
    </div>
</div>

</body>
</html>
