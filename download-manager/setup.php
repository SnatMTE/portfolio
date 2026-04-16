<?php
/**
 * setup.php
 *
 * First-run setup script. Creates the initial admin user account.
 *
 * IMPORTANT: Delete or protect this file immediately after running it.
 * Leaving it accessible in production is a security risk.
 *
 * Usage:
 *   1. Open /download-manager/setup.php in your browser.
 *   2. Fill in the username, email, and password for the admin account.
 *   3. Submit the form.
 *   4. DELETE this file from the server.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Setup is only relevant in standalone mode; CMS handles its own users
if (defined('DM_CMS_MODE') && DM_CMS_MODE) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;padding:2rem;">Setup not required in CMS mode. Use the CMS user management instead.</p>');
}

// Show helpful errors on localhost
$devMode = false;
$host    = $_SERVER['HTTP_HOST'] ?? '';
if (str_contains($host, 'localhost') || str_starts_with($host, '127.')) {
    $devMode = true;
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/**
 * Checks whether any admin user exists in dm_users.
 *
 * Prevents the setup page from creating additional accounts after initial setup.
 *
 * @return bool  True when at least one user row exists.
 */
function dmAdminExists(): bool
{
    return (int) getDB()->query('SELECT COUNT(*) FROM dm_users')->fetchColumn() > 0;
}

try {
    if (dmAdminExists()) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">Setup already complete. Please delete setup.php from your server.</p>');
    }
} catch (Throwable $e) {
    error_log('DM setup init error: ' . $e->getMessage());
    if ($devMode) {
        die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">Error: ' . e($e->getMessage()) . '</p>');
    }
    http_response_code(500);
    die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">An internal error occurred.</p>');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password2']      ?? '';

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
            $stmt = getDB()->prepare(
                'INSERT INTO dm_users (username, password, email) VALUES (:username, :password, :email)'
            );
            $stmt->execute([
                ':username' => $username,
                ':password' => $hash,
                ':email'    => $email,
            ]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup – Download Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<main class="site-main">
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <h1>&#9881; Download Manager Setup</h1>

                <?php if ($success): ?>
                    <div class="alert alert--success" role="alert">
                        Admin account created successfully!
                        <strong><a href="<?= SITE_URL ?>/login.php">Log in now →</a></strong>
                    </div>
                    <p style="color:var(--clr-text-muted);font-size:.85rem;text-align:center;margin-top:1rem;">
                        Remember to <strong>delete setup.php</strong> from your server.
                    </p>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert--error" role="alert"><?= e($err) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="post" action="<?= SITE_URL ?>/setup.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input id="username" type="text" name="username" class="form-control"
                                   minlength="3" maxlength="80" required
                                   value="<?= e($_POST['username'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input id="email" type="email" name="email" class="form-control"
                                   maxlength="180" required
                                   value="<?= e($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" class="form-control"
                                   minlength="8" autocomplete="new-password" required>
                        </div>

                        <div class="form-group">
                            <label for="password2">Confirm Password</label>
                            <input id="password2" type="password" name="password2" class="form-control"
                                   minlength="8" autocomplete="new-password" required>
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
