<?php
/**
 * cms/admin/settings.php  —  Site settings
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/auth.php';
requireCMSAdmin();

$flash = cmsGetFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cmsValidateCsrf($_POST['csrf_token'] ?? '')) {
        cmsFlashMessage('Invalid security token.', 'error');
    } else {
        $allowed = ['site_name', 'site_tagline', 'allow_reg'];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                setSetting($key, trim($_POST[$key]));
            }
        }
        cmsFlashMessage('Settings saved.', 'success');
    }
    redirect(SITE_URL . '/admin/settings.php');
}

$siteName    = getSetting('site_name', 'My CMS');
$siteTagline = getSetting('site_tagline', 'Powered by CMS');
$allowReg    = getSetting('allow_reg', '0');

$pageTitle = 'Settings';
require_once CMS_ROOT . '/templates/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1>Settings</h1>
</div>

<div class="card">
    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= cmsCsrfToken() ?>">

        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input type="text" id="site_name" name="site_name"
                   value="<?= e($siteName) ?>" maxlength="120" required>
        </div>

        <div class="form-group">
            <label for="site_tagline">Tagline</label>
            <input type="text" id="site_tagline" name="site_tagline"
                   value="<?= e($siteTagline) ?>" maxlength="255">
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="allow_reg" value="1"
                    <?= $allowReg === '1' ? 'checked' : '' ?>>
                Allow public user registration
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save Settings</button>
        </div>
    </form>
</div>

<?php require_once CMS_ROOT . '/templates/admin_footer.php'; ?>
