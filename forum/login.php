<?php
/**
 * login.php
 *
 * Handles user login via a username/email and password form.
 * On success, stores the user ID in the session and redirects.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

// When inside CMS, redirect to the shared CMS login page
if (defined('CMS_ROOT')) {
    $cmsLogin = defined('CMS_URL') ? CMS_URL . '/login.php' : '../login.php';
    header('Location: ' . $cmsLogin);
    exit;
}

// Already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$errors    = [];
$formLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $formLogin = trim($_POST['login'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($formLogin === '') {
        $errors[] = 'Please enter your username or email.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        // Look up by username or email
        $stmt = getDB()->prepare(
            "SELECT u.id, u.username, u.password_hash, r.name AS role
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.username = :login OR u.email = :login"
        );
        $stmt->execute([':login' => $formLogin]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            flashMessage('Welcome back, ' . $user['username'] . '!', 'success');
            redirect(SITE_URL . '/');
        } else {
            // Generic error to prevent username enumeration
            $errors[] = 'Invalid username/email or password.';
        }
    }
}

$pageTitle = 'Log In';
require_once __DIR__ . '/templates/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Log In</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error" role="alert">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= SITE_URL ?>/login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" name="login" id="login" class="form-control"
                       value="<?= e($formLogin) ?>"
                       autocomplete="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--full">Log In</button>
        </form>

        <p class="auth-alt">
            Don't have an account?
            <a href="<?= SITE_URL ?>/register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
