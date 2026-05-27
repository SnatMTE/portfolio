<?php
/**
 * crm/setup.php
 *
 * One-time CRM setup script.
 * In standalone mode: initialises the schema and creates the first admin user.
 * In CMS-integrated mode: initialises the schema only (users come from the CMS).
 * Delete or protect this file after running.
 */

define('CRM_SETUP', true);

require_once __DIR__ . '/config.php';

$pdo = getCRMDB();

// Determine whether we are in standalone mode.
// standalone_boot.php defines CRM_STANDALONE when no parent CMS is present.
$standalone = defined('CRM_STANDALONE');

$error  = '';
$success = false;

// ---------------------------------------------------------------------------
// Standalone: create first admin user via form.
// ---------------------------------------------------------------------------
if ($standalone) {
    // Only show the form if no admin exists yet.
    $adminExists = (int) $pdo->query(
        "SELECT COUNT(*) FROM crm_users u
         JOIN crm_roles r ON r.id = u.role_id
         WHERE r.name = 'admin'"
    )->fetchColumn() > 0;

    if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid form submission. Please try again.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm'] ?? '';

            if ($username === '' || $email === '' || $password === '') {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                $stmt = $pdo->prepare(
                    "INSERT INTO crm_users (username, email, password_hash, role_id)
                     VALUES (:username, :email, :hash, 1)"
                );
                $stmt->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':hash'     => $hash,
                ]);
                $success = true;
                cmsFlashMessage('Admin account created. You can now log in.', 'success');
            }
        }
    }
}

$tables = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'crm_%'"
)->fetchAll(PDO::FETCH_COLUMN);

$seeded = false;
if (file_exists(__DIR__ . '/DEMO') && function_exists('seedDemoCRM')) {
    seedDemoCRM($pdo);
    $seeded = true;
}

// Redirect to login after successful admin creation.
if ($success) {
    header('Location: ' . CRM_URL . '/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM Setup</title>
    <style>
        body       { font-family: system-ui, sans-serif; max-width: 600px; margin: 4rem auto; padding: 0 1rem; color: #111; }
        h1         { color: #f97316; }
        h2         { color: #1e293b; margin-top: 2rem; }
        .ok        { color: #16a34a; }
        .info      { color: #2563eb; }
        ul         { line-height: 2; }
        .form-group { margin-bottom: 1rem; }
        label      { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .3rem; }
        input[type="text"], input[type="email"], input[type="password"] {
                     width: 100%; box-sizing: border-box; padding: .5rem .75rem;
                     border: 1px solid #d1d5db; border-radius: .375rem; font-size: 1rem; }
        .btn       { background: #f97316; color: #fff; border: none; padding: .6rem 1.25rem;
                     border-radius: .375rem; font-size: 1rem; cursor: pointer; }
        .alert-err { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;
                     border-radius: .375rem; padding: .75rem 1rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>&#x2699;&#xFE0F; CRM Setup</h1>

    <?php if ($standalone && !isset($adminExists)): ?>
        <?php /* $adminExists is set above */ ?>
    <?php endif; ?>

    <?php if ($standalone && !$adminExists): ?>
        <h2>Create Admin Account</h2>
        <p>No admin user exists yet. Fill in the form below to get started.</p>

        <?php if ($error !== ''): ?>
            <div class="alert-err"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(cmsCsrfToken(), ENT_QUOTES) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required>
            </div>
            <button type="submit" class="btn">Create Admin</button>
        </form>
    <?php elseif ($standalone): ?>
        <p class="ok">&#10003; Admin account already exists.</p>
    <?php endif; ?>

    <h2>Database Tables</h2>
    <ul>
        <?php foreach ($tables as $t): ?>
            <li class="ok">&#10003; <?= htmlspecialchars($t, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($seeded): ?>
        <p class="info">&#128310; Demo data seeded from <code>db/demo_seed.php</code>.</p>
    <?php endif; ?>

    <p><strong>Important:</strong> Delete or restrict access to <code>setup.php</code> after setup.</p>
    <p><a href="<?= htmlspecialchars(CRM_URL . '/', ENT_QUOTES) ?>">&#8594; Go to CRM</a></p>
</body>
</html>
