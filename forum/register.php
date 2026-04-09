<?php
/**
 * register.php
 *
 * Handles new user registration.
 * Validates input, hashes the password with password_hash(), and inserts the user.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// Already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$errors      = [];
$formUser    = '';
$formEmail   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $formUser  = trim($_POST['username'] ?? '');
    $formEmail = trim($_POST['email']    ?? '');
    $password  = $_POST['password']      ?? '';
    $confirm   = $_POST['confirm']       ?? '';

    // Validate username
    if ($formUser === '') {
        $errors[] = 'Username cannot be empty.';
    } elseif (mb_strlen($formUser) < 3 || mb_strlen($formUser) > 40) {
        $errors[] = 'Username must be between 3 and 40 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $formUser)) {
        $errors[] = 'Username may only contain letters, numbers, hyphens, and underscores.';
    }

    // Validate email
    if ($formEmail === '') {
        $errors[] = 'Email address cannot be empty.';
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check for duplicate username / email
    if (empty($errors)) {
        $stmt = getDB()->prepare(
            "SELECT id, username, email FROM users
             WHERE username = :username OR email = :email"
        );
        $stmt->execute([':username' => $formUser, ':email' => $formEmail]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (strtolower($existing['username']) === strtolower($formUser)) {
                $errors[] = 'That username is already taken.';
            } else {
                $errors[] = 'That email address is already registered.';
            }
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = getDB()->prepare(
            "INSERT INTO users (username, email, password_hash, role_id)
             VALUES (:username, :email, :hash, 2)"
        );
        $stmt->execute([
            ':username' => $formUser,
            ':email'    => $formEmail,
            ':hash'     => $hash,
        ]);
        $newId = (int) getDB()->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $newId;
        flashMessage('Welcome to ' . FORUM_NAME . ', ' . $formUser . '!', 'success');
        redirect(SITE_URL . '/');
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/templates/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card--wide">
        <h1>Create an Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error" role="alert">
                <ul class="error-list">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control"
                       value="<?= e($formUser) ?>"
                       autocomplete="username" required autofocus
                       minlength="3" maxlength="40"
                       pattern="[a-zA-Z0-9_\-]+"
                       title="Letters, numbers, hyphens, and underscores only">
                <small class="form-hint">3-40 characters. Letters, numbers, hyphens, and underscores.</small>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= e($formEmail) ?>"
                       autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control"
                       autocomplete="new-password" required minlength="8">
                <small class="form-hint">At least 8 characters.</small>
            </div>

            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" name="confirm" id="confirm" class="form-control"
                       autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--full">Create Account</button>
        </form>

        <p class="auth-alt">
            Already have an account?
            <a href="<?= SITE_URL ?>/login.php">Log in here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
