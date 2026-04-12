<?php
/**
 * calendar/setup.php
 *
 * First-run setup script (standalone mode only).
 * Creates the initial admin user account.
 *
 * IMPORTANT: Delete this file immediately after running it.
 * Leaving setup.php accessible is a security risk.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Not needed in CMS mode — defer to CMS user management
if (defined('CMS_ROOT')) {
    http_response_code(404);
    exit('Setup is managed by the CMS. Please use the CMS admin panel.');
}

// Enable helpful error display on localhost
$devMode = false;
$host    = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') === 0) {
    $devMode = true;
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/**
 * Checks whether any admin user already exists in the database.
 *
 * @return bool  TRUE if at least one user exists.
 */
function adminExists(): bool
{
    return (int) getDB()->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
}

if (adminExists()) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:#991b1b;padding:2rem;">Setup already complete. Delete setup.php from your server.</p>');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf(trim($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid form submission.';
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
                "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)"
            );
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hash,
            ]);
            $success = true;
        }
    }
}

$pageTitle = 'Initial Setup';
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
        <div class="auth-wrapper">
            <div class="auth-card">
                <h1>&#128197; Calendar Setup</h1>

                <?php if ($success): ?>
                    <div class="alert alert--success">
                        Admin account created successfully! You can now
                        <a href="<?= SITE_URL ?>/login.php">log in</a>.
                        <strong>Please delete <code>setup.php</code> now.</strong>
                    </div>
                <?php else: ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert--error">
                            <ul style="margin:0;padding-left:1.25rem;">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= e($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted" style="margin-bottom:1.25rem;">
                        Create the administrator account. Delete this file when done.
                    </p>

                    <form method="post" action="<?= SITE_URL ?>/setup.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username"
                                   class="form-input" minlength="3" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email"
                                   class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password"
                                   class="form-input" minlength="8" required>
                        </div>

                        <div class="form-group">
                            <label for="password2" class="form-label">Confirm Password</label>
                            <input type="password" id="password2" name="password2"
                                   class="form-input" required>
                        </div>

                        <button type="submit" class="btn btn--primary btn--full">
                            Create Admin Account
                        </button>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>
