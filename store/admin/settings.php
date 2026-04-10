<?php
/**
 * admin/settings.php
 *
 * Basic settings editor for the store module. Edits a small set of
 * configuration values in `config.php` and creates a timestamped backup
 * before saving.
 */

require_once __DIR__ . '/auth.php';

$admin = currentAdminUser();
$currentAdminPage = 'settings';

$cfgPath = ROOT_PATH . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['csrf_token'] ?? '');
    if (!validateCsrf($token)) {
        flashMessage('Invalid request. Please try again.', 'error');
        redirect(SITE_URL . '/admin/settings.php');
    }

    $fields = [
        'SITE_NAME'         => 'string',
        'SITE_TAGLINE'      => 'string',
        'PRODUCTS_PER_PAGE' => 'int',
        'CURRENCY'          => 'string',
        'PAYPAL_MODE'       => 'string',
        'PAYPAL_CLIENT_ID'  => 'string',
        'PAYPAL_SECRET'     => 'string',
    ];

    $newValues = [];
    foreach ($fields as $k => $type) {
        if ($type === 'int') {
            $newValues[$k] = (int) ($_POST[$k] ?? 0);
        } else {
            $newValues[$k] = trim((string) ($_POST[$k] ?? ''));
        }
    }

    if (!is_writable($cfgPath)) {
        flashMessage('Cannot write to config.php — file not writable.', 'error');
        redirect(SITE_URL . '/admin/settings.php');
    }

    $configContent = file_get_contents($cfgPath);
    if ($configContent === false) {
        flashMessage('Could not read config.php', 'error');
        redirect(SITE_URL . '/admin/settings.php');
    }

    // Backup current config
    $backupPath = $cfgPath . '.bak.' . date('YmdHis');
    if (!copy($cfgPath, $backupPath)) {
        flashMessage('Failed to create backup of config.php', 'error');
        redirect(SITE_URL . '/admin/settings.php');
    }

    // Replace each define(...) occurrence with a new value
    foreach ($newValues as $name => $value) {
        $pattern = "/(define\\(\\s*'" . preg_quote($name, '/') . "'\\s*,\\s*)(.*?)(\\s*\\);)/s";
        $configContent = preg_replace_callback($pattern, function ($m) use ($value) {
            return $m[1] . var_export($value, true) . $m[3];
        }, $configContent, 1);
    }

    if (file_put_contents($cfgPath, $configContent, LOCK_EX) === false) {
        flashMessage('Failed to write config.php', 'error');
        redirect(SITE_URL . '/admin/settings.php');
    }

    flashMessage('Settings saved. Backup created: ' . basename($backupPath), 'success');
    redirect(SITE_URL . '/admin/settings.php');
}

$pageTitle = 'Settings – ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Settings – <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once dirname(__DIR__) . '/templates/admin_nav.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Settings</h1>
            <span>Manage basic store configuration</span>
        </div>

        <?php renderFlash(); ?>

        <form method="post" action="<?= SITE_URL ?>/admin/settings.php" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-row">
                <label for="site_name">Site name</label>
                <input id="site_name" name="SITE_NAME" type="text" value="<?= e(SITE_NAME) ?>">
            </div>

            <div class="form-row">
                <label for="site_tagline">Tagline</label>
                <input id="site_tagline" name="SITE_TAGLINE" type="text" value="<?= e(SITE_TAGLINE) ?>">
            </div>

            <div class="form-row">
                <label for="products_per_page">Products per page</label>
                <input id="products_per_page" name="PRODUCTS_PER_PAGE" type="number" min="1" value="<?= (int) PRODUCTS_PER_PAGE ?>">
            </div>

            <div class="form-row">
                <label for="currency">Currency</label>
                <input id="currency" name="CURRENCY" type="text" value="<?= e(CURRENCY) ?>">
            </div>

            <fieldset class="form-section">
                <legend>PayPal</legend>

                <div class="form-row">
                    <label for="paypal_mode">Mode</label>
                    <select id="paypal_mode" name="PAYPAL_MODE">
                        <option value="sandbox" <?= PAYPAL_MODE === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                        <option value="live" <?= PAYPAL_MODE === 'live' ? 'selected' : '' ?>>Live</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="paypal_client">Client ID</label>
                    <input id="paypal_client" name="PAYPAL_CLIENT_ID" type="text" value="<?= e(PAYPAL_CLIENT_ID) ?>">
                </div>

                <div class="form-row">
                    <label for="paypal_secret">Secret</label>
                    <input id="paypal_secret" name="PAYPAL_SECRET" type="text" value="<?= e(PAYPAL_SECRET) ?>">
                </div>
            </fieldset>

            <div class="form-row form-row--actions">
                <button type="submit" class="btn btn--primary">Save settings</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
