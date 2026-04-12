<?php
/**
 * setup.php
 *
 * First-run setup script for the Forum. Creates the initial admin user account.
 * IMPORTANT: Delete or rename this file immediately after running it.
 * Leaving this file accessible in production is a serious security risk.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

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
// Prevent reuse after initial setup
// ---------------------------------------------------------------------------

/**
 * Returns true when an administrator user already exists in the forum.
 *
 * @return bool
 */
function adminExists(): bool
{
    return (int) getDB()->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
}

try {
    if (adminExists()) {
        http_response_code(403);
        die('<p style="font-family:Inter,Arial,sans-serif;color:#991b1b;padding:2rem;">Setup already complete. Please delete setup.php from your server.</p>');
    }
} catch (Throwable $e) {
    error_log('Setup init error: ' . $e->getMessage());
    if ($devMode) {
        die('<p style="font-family:Inter,Arial,sans-serif;color:#991b1b;padding:2rem;">Internal error: ' . e($e->getMessage()) . '</p>');
    }
    http_response_code(500);
    die('<p style="font-family:Inter,Arial,sans-serif;color:#991b1b;padding:2rem;">An internal error occurred.</p>');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();

        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if ($username === '' || mb_strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }

        // Check for duplicate username / email
        if (empty($errors)) {
            $stmt = getDB()->prepare(
                "SELECT id, username, email FROM users WHERE username = :username OR email = :email"
            );
            $stmt->execute([':username' => $username, ':email' => $email]);
            $existing = $stmt->fetch();
            if ($existing) {
                if (strtolower($existing['username']) === strtolower($username)) {
                    $errors[] = 'That username is already taken.';
                } else {
                    $errors[] = 'That email address is already registered.';
                }
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = getDB()->prepare(
                "INSERT INTO users (username, email, password_hash, role_id) VALUES (:username, :email, :hash, 1)"
            );
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':hash'     => $hash,
            ]);
            $success = true;
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

$pageTitle = 'Setup';
require_once __DIR__ . '/templates/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card--wide">
        <h1>&#9881; <?= e(FORUM_NAME) ?> — Initial Setup</h1>

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
                Create your administrator account. Delete <code>setup.php</code> after completing setup.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert--error">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= SITE_URL ?>/setup.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

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

<?php require_once __DIR__ . '/templates/footer.php'; ?>
