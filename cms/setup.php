<?php
/**
 * cms/setup.php
 *
 * First-run setup wizard.
 * Creates the first admin account and configures basic site settings.
 *
 * This file should be deleted or protected after setup is complete.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Redirect to admin if an admin user already exists
$adminCount = (int) getCMSDB()->query(
    "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'admin'"
)->fetchColumn();

if ($adminCount > 0) {
    cmsFlashMessage('Setup already completed. Please log in.', 'info');
    redirect(SITE_URL . '/login.php');
}

$errors = [];
$done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $siteName = trim($_POST['site_name'] ?? '');
        $username = trim($_POST['username']  ?? '');
        $email    = trim($_POST['email']     ?? '');
        $password = $_POST['password']       ?? '';
        $confirm  = $_POST['confirm']        ?? '';

        if ($siteName === '') $errors[] = 'Site name is required.';

        if ($username === '' || mb_strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
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
            $db = getCMSDB();

            // Save site settings
            setSetting('site_name',    $siteName);
            setSetting('site_tagline', trim($_POST['site_tagline'] ?? ''));

            // Create admin user
            $adminRoleId = (int) $db->query(
                "SELECT id FROM roles WHERE name = 'admin' LIMIT 1"
            )->fetchColumn();

            $stmt = $db->prepare(
                "INSERT INTO users (username, email, password_hash, role_id)
                 VALUES (:username, :email, :password_hash, :role_id)"
            );
            $stmt->execute([
                ':username'      => $username,
                ':email'         => $email,
                ':password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                ':role_id'       => $adminRoleId,
            ]);

            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:480px">
        <?php if ($done): ?>
            <h1 style="text-align:center">&#9989; Setup Complete</h1>
            <p style="text-align:center;color:var(--color-muted);margin:1rem 0 1.5rem">
                Your CMS is ready. Log in with the credentials you just created.
            </p>
            <div style="text-align:center">
                <a href="<?= SITE_URL ?>/login.php" class="btn btn--primary">Go to Login</a>
            </div>
        <?php else: ?>
            <h1>&#128640; CMS Setup</h1>
            <p style="color:var(--color-muted);margin-bottom:1.5rem;font-size:.875rem">
                Configure your site and create the first admin account.
            </p>

            <?php if ($errors): ?>
                <div class="alert alert--error">
                    <ul class="error-list">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">

                <h3 style="margin-bottom:1rem;border-bottom:1px solid var(--color-border);padding-bottom:.5rem">
                    Site Settings
                </h3>

                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name"
                           value="<?= e($_POST['site_name'] ?? 'My CMS') ?>"
                           maxlength="120" required autofocus>
                </div>

                <div class="form-group">
                    <label for="site_tagline">Tagline <span class="hint">(optional)</span></label>
                    <input type="text" id="site_tagline" name="site_tagline"
                           value="<?= e($_POST['site_tagline'] ?? '') ?>" maxlength="255">
                </div>

                <h3 style="margin:1.5rem 0 1rem;border-bottom:1px solid var(--color-border);padding-bottom:.5rem">
                    Admin Account
                </h3>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?= e($_POST['username'] ?? '') ?>"
                           maxlength="40" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           maxlength="255" required autocomplete="email">
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

                <div class="form-actions" style="border:none;padding-top:0">
                    <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center">
                        Complete Setup
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
