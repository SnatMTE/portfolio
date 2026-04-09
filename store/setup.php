<?php
/**
 * setup.php
 *
 * One-time setup wizard for the portfolio store.
 *
 * Creates the first admin user account. This file should be deleted
 * or access-restricted once setup is complete.
 *
 * Access
 * ------
 *   This page is intentionally accessible without authentication so
 *   the very first admin account can be created. After creation it
 *   redirects to the login page.
 *
 *   IMPORTANT: Delete or rename this file after first use.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Prevent re-running setup if an admin already exists.
$adminCount = (int) getDB()->query("SELECT COUNT(*) FROM users")->fetchColumn();

$alreadySetup = $adminCount > 0;
$success      = false;
$errors       = [];

if (!$alreadySetup && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');

    if (!validateCsrf($token)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        if ($username === '') $errors[] = 'Username is required.';
        elseif (mb_strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        elseif (mb_strlen($username) > 60) $errors[] = 'Username must be 60 characters or fewer.';
        elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) $errors[] = 'Username may only contain letters, numbers, hyphens, and underscores.';

        if ($email === '') $errors[] = 'Email address is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

        if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        elseif ($password !== $password2) $errors[] = 'Passwords do not match.';

        if (count($errors) === 0) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);

            $stmt = getDB()->prepare("
                INSERT INTO users (username, password, email) VALUES (:username, :password, :email)
            ");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hash,
                ':email'    => $email,
            ]);

            $success = true;
        }
    }
}

$pageTitle = 'Store Setup';
$metaDesc  = 'Initial setup for the store admin account.';
require_once __DIR__ . '/templates/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>&#9881;&#65039; Store Setup</h1>

        <?php if ($alreadySetup): ?>
            <div class="alert alert--info" role="alert">
                Setup is already complete. Please
                <a href="<?= SITE_URL ?>/login.php">log in</a>.
            </div>

        <?php elseif ($success): ?>
            <div class="alert alert--success" role="alert">
                Admin account created! You can now
                <a href="<?= SITE_URL ?>/login.php">log in</a>.
            </div>
            <p><strong>Remember to delete setup.php</strong> from your server.</p>

        <?php else: ?>

            <?php if (count($errors) > 0): ?>
                <div class="alert alert--error" role="alert">
                    <ul class="error-list">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p class="setup-intro">
                Create your first admin account to access the store management panel.
            </p>

            <form method="post" action="<?= SITE_URL ?>/setup.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" class="form-control"
                           required minlength="3" maxlength="60"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" class="form-control"
                           required maxlength="254"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password <small>(min. 8 characters)</small></label>
                    <input id="password" type="password" name="password" class="form-control"
                           required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input id="password_confirm" type="password" name="password_confirm"
                           class="form-control" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn--primary btn--block">Create Admin Account</button>
            </form>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
