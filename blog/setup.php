<?php
/**
 * setup.php
 *
 * First-run setup script. Creates the initial admin user account.
 *
 * IMPORTANT: Delete or rename this file immediately after running it.
 * Leaving this file accessible in production is a security risk.
 *
 * Usage:
 *   1. Open this URL in your browser: <?= SITE_URL ?>/setup.php
 *   2. Fill in the username, email and password for the admin account.
 *   3. Submit the form.
 *   4. DELETE this file from your server.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Enable helpful error display on localhost for easier debugging
$devMode = false;
if (php_sapi_name() !== 'cli') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') === 0 || strpos(SITE_URL, 'localhost') !== false) {
        $devMode = true;
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }
}

// ---------------------------------------------------------------------------
// Block the page if an admin user already exists
// ---------------------------------------------------------------------------

/**
 * Checks whether any user rows exist in the database.
 *
 * Used to prevent the setup page from being used to create additional
 * admin accounts after the initial setup has been completed.
 *
 * @return bool  TRUE if at least one user exists in the database.
 */
function adminExists(): bool
{
    return (int) getDB()->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
}

try {
    if (adminExists()) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">Setup already complete. Please delete setup.php from your server.</p>');
    }
} catch (Throwable $e) {
    error_log('Setup init error: ' . $e->getMessage());
    if ($devMode) {
        die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">Internal error: ' . e($e->getMessage()) . '</p>');
    }
    http_response_code(500);
    die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">An internal error occurred.</p>');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
            $errors[] = 'Invalid form submission.';
        } else {
            $username  = trim($_POST['username'] ?? '');
            $email     = trim($_POST['email']    ?? '');
            $password  = $_POST['password']      ?? '';
            $password2 = $_POST['password2']     ?? '';

            if ($username === '' || mb_strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if ($password !== $password2) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                $stmt = getDB()->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash,
                    ':email'    => $email,
                ]);
                $success = true;
            }
        }
    } catch (Throwable $e) {
        error_log('Setup POST error: ' . $e->getMessage());
        if ($devMode) {
            $errors[] = 'Internal error: ' . e($e->getMessage());
        } else {
            $errors[] = 'An internal error occurred. Check the server log.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup – <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<main class="site-main">
<div class="container">
<div class="auth-wrapper" style="min-height:auto;padding:3rem 0;">
    <div class="auth-card" style="max-width:480px;">
        <h1 style="font-size:1.4rem;margin-bottom:1.5rem;">&#9881; Blog Setup</h1>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <strong>Admin account created!</strong>
                <p style="margin-top:.4rem;">
                    You can now <a href="<?= SITE_URL ?>/login.php">log in</a>.
                    <strong>Remember to delete this file!</strong>
                </p>
            </div>
        <?php else: ?>
            <p style="color:var(--clr-text-muted);font-size:.9rem;margin-bottom:1.25rem;">
                Create your admin account. Delete <code>setup.php</code> after completing setup.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert--error">
                    <?php foreach ($errors as $e): ?>
                        <p><?= e($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label for="s-username">Username</label>
                    <input id="s-username" type="text" name="username" class="form-control"
                           required minlength="3" maxlength="80"
                           value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="s-email">Email</label>
                    <input id="s-email" type="email" name="email" class="form-control"
                           required maxlength="200"
                           value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="s-password">Password</label>
                    <input id="s-password" type="password" name="password" class="form-control"
                           required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="s-password2">Confirm Password</label>
                    <input id="s-password2" type="password" name="password2" class="form-control"
                           required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn--primary btn--full">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</div>
</main>
</body>
</html>
